<?php

namespace App\Http\Controllers\Production;

use App\Enums\BuildOrderStatus;
use App\Enums\FormulaStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Production\StoreBuildOrderRequest;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InventoryBalance;
use App\Models\Location;
use App\Models\ProductionRun;
use App\Models\User;
use App\Support\PerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Builds (build orders) plan units of an innovation from its active formula, consuming parts at one location.
 */
class BuildOrderController extends Controller
{
    /**
     * Relations every presented order needs.
     *
     * @var list<string>
     */
    private const DISPLAY_RELATIONS = ['innovation:id,innovation_code,name', 'formula', 'location:id,code,name', 'requester:id,name'];

    /**
     * List builds with search, status, innovation, and location filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'innovation', 'location']);
        $user = $request->user();
        $canCreate = $user->can('create', BuildOrder::class);

        $orders = BuildOrder::query()
            ->with(self::DISPLAY_RELATIONS)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->search($search))
            ->when(BuildOrderStatus::tryFrom($filters['status'] ?? ''), fn (Builder $query, BuildOrderStatus $status) => $query->where('status', $status))
            ->when($filters['innovation'] ?? null, fn (Builder $query, string $innovation) => $query->where('innovation_id', $innovation))
            ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location_id', $location))
            ->latest()
            ->orderByDesc('id')
            ->paginate(PerPage::fromRequest($request))
            ->withQueryString();

        $completions = $this->completionPreviews($orders->getCollection()->filter(fn (BuildOrder $order): bool => $user->can('complete', $order)));

        return Inertia::render('production/build-orders/Index', [
            'orders' => $orders->through(fn (BuildOrder $order): array => $this->present($order, $user, $completions[$order->id] ?? null)),
            'filters' => $filters,
            'options' => [
                'statuses' => BuildOrderStatus::options(),
                'innovations' => Innovation::query()
                    ->where(fn (Builder $query) => $query->has('formulas')->orHas('buildOrders'))
                    ->orderBy('name')
                    ->get(['id', 'innovation_code', 'name'])
                    ->map(fn (Innovation $innovation): array => ['value' => $innovation->id, 'label' => $innovation->name]),
                'locations' => Location::query()->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Location $location): array => ['value' => $location->id, 'label' => $location->name]),
                'buildableInnovations' => $canCreate ? Innovation::query()
                    ->with('activeFormula:id,innovation_id,version')
                    ->whereHas('activeFormula')
                    ->orderBy('name')
                    ->get(['id', 'innovation_code', 'name'])
                    ->map(fn (Innovation $innovation): array => [
                        'value' => $innovation->id,
                        'label' => "{$innovation->name} (v{$innovation->activeFormula->version})",
                    ]) : [],
                'activeLocations' => $canCreate ? Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                    ->map(fn (Location $location): array => ['value' => $location->id, 'label' => $location->name]) : [],
            ],
            'openCreate' => $canCreate && $request->boolean('create'),
            'can' => ['create' => $canCreate],
        ]);
    }

    /**
     * Show a build with its progress, status actions, and every production run recorded against it.
     */
    public function show(Request $request, BuildOrder $buildOrder): Response
    {
        Gate::authorize('view', $buildOrder);

        $user = $request->user();

        $buildOrder->load([
            ...self::DISPLAY_RELATIONS,
            'productionRuns' => fn ($query) => $query->with('completer:id,name')->latest('completed_at')->orderByDesc('id'),
        ]);

        $completion = $user->can('complete', $buildOrder)
            ? ($this->completionPreviews(new Collection([$buildOrder]))[$buildOrder->id] ?? null)
            : null;

        return Inertia::render('production/build-orders/Show', [
            'order' => $this->present($buildOrder, $user, $completion),
            'runs' => $buildOrder->productionRuns->map(fn (ProductionRun $run): array => [
                'id' => $run->id,
                'run_number' => $run->run_number,
                'url' => route('production.runs.show', $run),
                'ledger_url' => route('inventory.ledger.index', ['reference' => $run->run_number]),
                'output_quantity' => (float) $run->output_quantity,
                'total_input_cost' => (float) $run->total_input_cost,
                'unit_production_cost' => (float) $run->unit_production_cost,
                'completed_at' => $run->completed_at->toIso8601String(),
                'completed_by' => $run->completer?->name,
            ])->values(),
        ]);
    }

    /**
     * Create a build order from the innovation's active formula, as a draft or already planned.
     */
    public function store(StoreBuildOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $order = DB::transaction(function () use ($data, $request): BuildOrder {
            $formula = InnovationFormula::query()
                ->where('innovation_id', $data['innovation_id'])
                ->where('status', FormulaStatus::Active)
                ->lockForUpdate()
                ->firstOrFail();

            return BuildOrder::create([
                'order_number' => BuildOrder::nextNumber(),
                'innovation_id' => $data['innovation_id'],
                'innovation_formula_id' => $formula->id,
                'location_id' => $data['location_id'],
                'planned_quantity' => $data['planned_quantity'],
                'produced_quantity' => 0,
                'status' => $data['plan'] ? BuildOrderStatus::Planned : BuildOrderStatus::Draft,
                'requested_by' => $request->user()->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Build :number created.', ['number' => $order->order_number])]);

        return to_route('production.build-orders.index');
    }

    /**
     * Move a draft order to planned.
     */
    public function plan(BuildOrder $buildOrder): RedirectResponse
    {
        return $this->transition($buildOrder, 'plan', BuildOrderStatus::Planned, __('Build planned.'));
    }

    /**
     * Start a planned order.
     */
    public function start(BuildOrder $buildOrder): RedirectResponse
    {
        return $this->transition($buildOrder, 'start', BuildOrderStatus::InProgress, __('Build started.'));
    }

    /**
     * Cancel an order that is not completed or already cancelled.
     */
    public function cancel(BuildOrder $buildOrder): RedirectResponse
    {
        return $this->transition($buildOrder, 'cancel', BuildOrderStatus::Cancelled, __('Build cancelled.'));
    }

    /**
     * Apply a status change under a row lock, re-checking the policy against the locked row.
     */
    private function transition(BuildOrder $buildOrder, string $ability, BuildOrderStatus $target, string $message): RedirectResponse
    {
        DB::transaction(function () use ($buildOrder, $ability, $target): void {
            $order = BuildOrder::query()->lockForUpdate()->findOrFail($buildOrder->id);

            Gate::authorize($ability, $order);

            $order->update([
                'status' => $target,
                'started_at' => $target === BuildOrderStatus::InProgress ? now() : $order->started_at,
            ]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => $message]);

        return back();
    }

    /**
     * The fields the Builds list, build detail, and run completion dialog share.
     *
     * @param  array{yield_quantity: float, lines: array<int, array<string, mixed>>}|null  $completion
     * @return array<string, mixed>
     */
    private function present(BuildOrder $order, User $user, ?array $completion): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'url' => route('production.build-orders.show', $order),
            'innovation' => [
                'id' => $order->innovation_id,
                'code' => $order->innovation?->innovation_code,
                'name' => $order->innovation?->name,
                'url' => route('innovations.show', ['innovation' => $order->innovation_id, 'formula' => $order->innovation_formula_id]),
            ],
            'formula_version' => $order->formula?->version,
            'location' => $order->location?->name,
            'planned_quantity' => (float) $order->planned_quantity,
            'produced_quantity' => (float) $order->produced_quantity,
            'remaining_quantity' => $order->remainingQuantity(),
            'progress' => $order->progressPercentage(),
            'status' => $order->status->value,
            'status_label' => $order->status->label(),
            'requester' => $order->requester?->name,
            'notes' => $order->notes,
            'created_at' => $order->created_at?->toIso8601String(),
            'started_at' => $order->started_at?->toIso8601String(),
            'completed_at' => $order->completed_at?->toIso8601String(),
            'completion' => $completion,
            'can' => [
                'plan' => $user->can('plan', $order),
                'start' => $user->can('start', $order),
                'cancel' => $user->can('cancel', $order),
                'complete' => $user->can('complete', $order),
            ],
        ];
    }

    /**
     * For each order that can record production: its formula's required lines with the stock available at the order's location.
     *
     * @param  Collection<int, BuildOrder>  $orders
     * @return array<int, array{yield_quantity: float, lines: array<int, array{inventory_item_id: int, item_code: string|null, item_name: string|null, unit_symbol: string|null, quantity_required: float, available: float, unit_cost: float}>}>
     */
    private function completionPreviews(Collection $orders): array
    {
        if ($orders->isEmpty()) {
            return [];
        }

        $orders->load([
            'formula.items' => fn ($query) => $query->where('is_optional', false)->orderBy('sort_order')->orderBy('id'),
            'formula.items.inventoryItem' => fn ($query) => $query->withTrashed(),
            'formula.items.unit:id,symbol',
        ]);

        $balances = InventoryBalance::query()
            ->whereIn('location_id', $orders->pluck('location_id')->unique())
            ->whereIn('inventory_item_id', $orders->flatMap(fn (BuildOrder $order) => $order->formula->items->pluck('inventory_item_id'))->unique())
            ->get()
            ->keyBy(fn (InventoryBalance $balance): string => "{$balance->inventory_item_id}:{$balance->location_id}");

        return $orders->mapWithKeys(fn (BuildOrder $order): array => [$order->id => [
            'yield_quantity' => (float) $order->formula->yield_quantity,
            'lines' => $order->formula->items->map(fn (InnovationItem $line): array => [
                'inventory_item_id' => $line->inventory_item_id,
                'item_code' => $line->inventoryItem?->item_code,
                'item_name' => $line->inventoryItem?->name,
                'unit_symbol' => $line->unit?->symbol,
                'quantity_required' => (float) $line->quantity_required,
                'available' => $balances->get("{$line->inventory_item_id}:{$order->location_id}")?->availableQuantity() ?? 0.0,
                'unit_cost' => (float) ($line->inventoryItem->unit_cost ?? 0),
            ])->values()->all(),
        ]])->all();
    }
}
