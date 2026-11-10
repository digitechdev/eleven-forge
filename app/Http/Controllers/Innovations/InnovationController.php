<?php

namespace App\Http\Controllers\Innovations;

use App\Enums\FormulaStatus;
use App\Enums\InnovationMediaType;
use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Enums\InventoryItemStatus;
use App\Enums\TechnologyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationRequest;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InnovationFormula;
use App\Models\InnovationItem;
use App\Models\InnovationMedia;
use App\Models\InnovationTechnology;
use App\Models\InventoryItem;
use App\Models\ProductionRun;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use App\Services\InnovationFormulaService;
use App\Support\PerPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InnovationController extends Controller
{
    /**
     * List innovations with search, category, type, status, visibility, and trashed filters.
     */
    public function index(Request $request): Response
    {
        $canRestore = $request->user()->can('restore', new Innovation);
        $filters = $request->only(['search', 'category', 'type', 'status', 'visibility', 'trashed']);

        if (! $canRestore) {
            unset($filters['trashed']);
        }

        $onlyTrashed = ($filters['trashed'] ?? null) === 'only';

        return Inertia::render('innovations/Index', [
            'innovations' => Innovation::query()
                ->with('category:id,name')
                ->when($onlyTrashed, fn (Builder $query) => $query->onlyTrashed())
                ->when($filters['search'] ?? null, fn (Builder $query, string $search) => $query->search($search))
                ->when($filters['category'] ?? null, fn (Builder $query, string $category) => $query->where('category_id', $category))
                ->when(InnovationType::tryFrom($filters['type'] ?? ''), fn (Builder $query, InnovationType $type) => $query->where('type', $type))
                ->when(InnovationStatus::tryFrom($filters['status'] ?? ''), fn (Builder $query, InnovationStatus $status) => $query->where('status', $status))
                ->when(InnovationVisibility::tryFrom($filters['visibility'] ?? ''), fn (Builder $query, InnovationVisibility $visibility) => $query->where('visibility', $visibility))
                ->latest('updated_at')
                ->orderBy('id')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (Innovation $innovation): array => [
                    'id' => $innovation->id,
                    'innovation_code' => $innovation->innovation_code,
                    'name' => $innovation->name,
                    'tagline' => $innovation->tagline,
                    'category' => $innovation->category?->name,
                    'type' => $innovation->type->value,
                    'type_label' => $innovation->type->label(),
                    'status' => $innovation->status->value,
                    'status_label' => $innovation->status->label(),
                    'visibility' => $innovation->visibility->value,
                    'visibility_label' => $innovation->visibility->label(),
                    'is_featured' => $innovation->is_featured,
                    'updated_at' => $innovation->updated_at?->toIso8601String(),
                    'deleted_at' => $innovation->deleted_at?->toIso8601String(),
                ]),
            'filters' => $filters,
            'options' => [
                'categories' => InnovationCategory::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name'])
                    ->map(fn (InnovationCategory $category): array => ['value' => $category->id, 'label' => $category->name]),
                'types' => InnovationType::options(),
                'statuses' => InnovationStatus::options(),
                'visibilities' => InnovationVisibility::options(),
            ],
            'can' => [
                'create' => $request->user()->can('create', Innovation::class),
                'restore' => $canRestore,
            ],
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): Response
    {
        Gate::authorize('create', Innovation::class);

        return Inertia::render('innovations/Create', [
            'options' => $this->formOptions(),
            'suggestedCode' => Innovation::nextCode(),
        ]);
    }

    /**
     * Create an innovation, generating its code and slug when left blank.
     */
    public function store(InnovationRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $innovation = Innovation::create([
            ...$data,
            'innovation_code' => filled($data['innovation_code'] ?? null) ? $data['innovation_code'] : Innovation::nextCode(),
            'slug' => filled($data['slug'] ?? null) ? $data['slug'] : Innovation::uniqueSlug($data['name']),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Innovation created.')]);

        return to_route('innovations.show', $innovation);
    }

    /**
     * Show an innovation with its formula versions, technologies, media, and production activity.
     */
    public function show(Request $request, Innovation $innovation): Response
    {
        Gate::authorize('view', $innovation);

        $innovation->load([
            'category:id,name',
            'creator:id,name',
            'updater:id,name',
            'formulas' => fn ($query) => $query->withExists('buildOrders')->orderBy('version'),
            'formulas.items' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
            'formulas.items.inventoryItem' => fn ($query) => $query->withTrashed(),
            'formulas.items.unit',
            'innovationTechnologies.technology',
            'media' => fn ($query) => $query->orderBy('sort_order')->orderBy('id'),
        ]);

        $innovation->formulas->each->setRelation('innovation', $innovation);

        $user = $request->user();
        $canUpdate = $user->can('update', $innovation);
        $formulas = $innovation->formulas->map(fn (InnovationFormula $formula): array => $this->presentFormula($formula, $user));
        $requestedFormulaId = $request->integer('formula');

        return Inertia::render('innovations/Show', [
            'innovation' => [
                ...$this->innovationFields($innovation),
                'category' => $innovation->category?->name,
                'type_label' => $innovation->type->label(),
                'status_label' => $innovation->status->label(),
                'visibility_label' => $innovation->visibility->label(),
                'creator' => $innovation->creator?->name,
                'updater' => $innovation->updater?->name,
                'created_at' => $innovation->created_at?->toIso8601String(),
                'updated_at' => $innovation->updated_at?->toIso8601String(),
                'estimated_unit_cost' => $innovation->estimated_unit_cost !== null ? (float) $innovation->estimated_unit_cost : null,
                'suggested_selling_price' => $innovation->suggested_selling_price !== null ? (float) $innovation->suggested_selling_price : null,
            ],
            'formulas' => $formulas,
            'selectedFormulaId' => ($formulas->firstWhere('id', $requestedFormulaId)
                ?? $formulas->firstWhere('status', FormulaStatus::Active->value)
                ?? $formulas->last())['id'] ?? null,
            'technologies' => $innovation->innovationTechnologies
                ->sortBy(fn (InnovationTechnology $link): string => strtolower($link->technology->name))
                ->values()
                ->map(fn (InnovationTechnology $link): array => [
                    'id' => $link->id,
                    'technology_id' => $link->technology_id,
                    'name' => $link->technology?->name,
                    'category' => $link->technology?->category,
                    'version' => $link->version,
                    'purpose' => $link->purpose,
                ]),
            'media' => $innovation->media->map(fn (InnovationMedia $media): array => [
                'id' => $media->id,
                'media_type' => $media->media_type->value,
                'url' => $media->url,
                'video_url' => $media->video_url,
                'title' => $media->title,
                'caption' => $media->caption,
                'alt_text' => $media->alt_text,
                'is_cover' => $media->is_cover,
                'is_public' => $media->is_public,
                'sort_order' => $media->sort_order,
            ]),
            'production' => $this->production($innovation),
            'options' => $canUpdate ? $this->detailOptions($innovation) : null,
            'can' => [
                'update' => $canUpdate,
                'delete' => $user->can('delete', $innovation),
                'createFormula' => $user->can('create', [InnovationFormula::class, $innovation]),
                'createBuildOrder' => $user->can('create', BuildOrder::class)
                    && $innovation->formulas->contains('status', FormulaStatus::Active),
            ],
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(Innovation $innovation): Response
    {
        Gate::authorize('update', $innovation);

        return Inertia::render('innovations/Edit', [
            'innovation' => $this->innovationFields($innovation),
            'options' => $this->formOptions($innovation->category_id),
        ]);
    }

    /**
     * Update an innovation.
     */
    public function update(InnovationRequest $request, Innovation $innovation): RedirectResponse
    {
        $data = $request->validated();

        $innovation->update([
            ...$data,
            'innovation_code' => filled($data['innovation_code'] ?? null) ? $data['innovation_code'] : $innovation->innovation_code,
            'slug' => filled($data['slug'] ?? null) ? $data['slug'] : Innovation::uniqueSlug($data['name'], $innovation->id),
            'suggested_selling_price' => InnovationFormulaService::suggestedSellingPrice($innovation->estimated_unit_cost, $data['markup_percentage'] ?? null),
            'updated_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Innovation updated.')]);

        return to_route('innovations.show', $innovation);
    }

    /**
     * Soft delete an innovation.
     */
    public function destroy(Request $request, Innovation $innovation): RedirectResponse
    {
        Gate::authorize('delete', $innovation);

        $innovation->forceFill(['updated_by' => $request->user()->id])->save();
        $innovation->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Innovation archived.')]);

        return to_route('innovations.index');
    }

    /**
     * Restore a soft-deleted innovation.
     */
    public function restore(Request $request, Innovation $innovation): RedirectResponse
    {
        Gate::authorize('restore', $innovation);

        $innovation->restore();
        $innovation->forceFill(['updated_by' => $request->user()->id])->save();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Innovation restored.')]);

        return back();
    }

    /**
     * The editable fields of an innovation, as sent to the form and detail pages.
     *
     * @return array<string, mixed>
     */
    private function innovationFields(Innovation $innovation): array
    {
        return [
            'id' => $innovation->id,
            'innovation_code' => $innovation->innovation_code,
            'name' => $innovation->name,
            'slug' => $innovation->slug,
            'category_id' => $innovation->category_id,
            'type' => $innovation->type->value,
            'tagline' => $innovation->tagline,
            'short_description' => $innovation->short_description,
            'description' => $innovation->description,
            'problem_statement' => $innovation->problem_statement,
            'solution_description' => $innovation->solution_description,
            'key_features' => $innovation->key_features ?? [],
            'benefits' => $innovation->benefits ?? [],
            'status' => $innovation->status->value,
            'visibility' => $innovation->visibility->value,
            'developed_at' => $innovation->developed_at?->toDateString(),
            'is_featured' => $innovation->is_featured,
            'markup_percentage' => $innovation->markup_percentage !== null ? (float) $innovation->markup_percentage : null,
        ];
    }

    /**
     * Select options for the create/edit form: active categories plus the current one.
     *
     * @return array{categories: array<int, array{value: int, label: string}>, types: array<int, array{value: string, label: string}>, statuses: array<int, array{value: string, label: string}>, visibilities: array<int, array{value: string, label: string}>}
     */
    private function formOptions(?int $currentCategoryId = null): array
    {
        return [
            'categories' => InnovationCategory::query()
                ->where(fn (Builder $query) => $query->where('is_active', true)->when($currentCategoryId, fn (Builder $query, int $id) => $query->orWhere('id', $id)))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (InnovationCategory $category): array => ['value' => $category->id, 'label' => $category->name])
                ->all(),
            'types' => InnovationType::options(),
            'statuses' => InnovationStatus::options(),
            'visibilities' => InnovationVisibility::options(),
        ];
    }

    /**
     * One formula version with its lines and what the user may do with it.
     *
     * @return array<string, mixed>
     */
    private function presentFormula(InnovationFormula $formula, User $user): array
    {
        $lines = $formula->items->map(function (InnovationItem $line): array {
            $unitCost = $line->inventoryItem?->unit_cost !== null ? (float) $line->inventoryItem->unit_cost : null;
            $quantity = (float) $line->quantity_required;

            return [
                'id' => $line->id,
                'inventory_item_id' => $line->inventory_item_id,
                'item_code' => $line->inventoryItem?->item_code,
                'item_name' => $line->inventoryItem?->name,
                'item_url' => $line->inventoryItem ? route('inventory.items.show', $line->inventoryItem) : null,
                'unit_id' => $line->unit_id,
                'unit_symbol' => $line->unit?->symbol,
                'quantity_required' => $quantity,
                'unit_cost' => $unitCost,
                'estimated_cost' => $unitCost !== null ? round($quantity * $unitCost, 2) : null,
                'usage_description' => $line->usage_description,
                'specification_notes' => $line->specification_notes,
                'is_optional' => $line->is_optional,
                'sort_order' => $line->sort_order,
            ];
        });

        $yield = (float) $formula->yield_quantity;
        $requiredCost = $lines->where('is_optional', false)->sum(fn (array $line): float => $line['estimated_cost'] ?? 0);

        return [
            'id' => $formula->id,
            'version' => $formula->version,
            'status' => $formula->status->value,
            'status_label' => $formula->status->label(),
            'yield_quantity' => $yield,
            'notes' => $formula->notes,
            'activated_at' => $formula->activated_at?->toIso8601String(),
            'estimated_unit_cost' => $formula->status !== FormulaStatus::Draft && $formula->estimated_unit_cost !== null
                ? (float) $formula->estimated_unit_cost
                : ($yield > 0 ? round($requiredCost / $yield, 2) : null),
            'is_used' => $formula->isUsed(),
            'items' => $lines,
            'total' => round($lines->sum(fn (array $line): float => $line['estimated_cost'] ?? 0), 2),
            'has_unpriced_lines' => $lines->contains(fn (array $line): bool => $line['estimated_cost'] === null),
            'can' => [
                'update' => $user->can('update', $formula),
                'activate' => $user->can('activate', $formula),
            ],
        ];
    }

    /**
     * The innovation's latest build orders and production runs, for the Production panel.
     *
     * @return array{buildOrders: array<int, array<string, mixed>>, runs: array<int, array<string, mixed>>, buildOrdersUrl: string, newBuildOrderUrl: string}
     */
    private function production(Innovation $innovation): array
    {
        return [
            'buildOrders' => $innovation->buildOrders()
                ->with('formula:id,version')
                ->latest()
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (BuildOrder $order): array => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'status' => $order->status->value,
                    'status_label' => $order->status->label(),
                    'formula_version' => $order->formula?->version,
                    'planned_quantity' => (float) $order->planned_quantity,
                    'produced_quantity' => (float) $order->produced_quantity,
                    'progress' => $order->progressPercentage(),
                ])
                ->all(),
            'runs' => $innovation->productionRuns()
                ->latest('completed_at')
                ->orderByDesc('id')
                ->limit(5)
                ->get()
                ->map(fn (ProductionRun $run): array => [
                    'id' => $run->id,
                    'run_number' => $run->run_number,
                    'output_quantity' => (float) $run->output_quantity,
                    'unit_production_cost' => (float) $run->unit_production_cost,
                    'completed_at' => $run->completed_at->toIso8601String(),
                    'url' => route('production.runs.show', $run),
                ])
                ->all(),
            'buildOrdersUrl' => route('production.build-orders.index', ['innovation' => $innovation->id]),
            'newBuildOrderUrl' => route('production.build-orders.index', ['innovation' => $innovation->id, 'create' => 1]),
        ];
    }

    /**
     * Select options for the BOM, technology, and media editors: active records plus those already linked.
     *
     * @return array{inventoryItems: array<int, array{value: int, label: string, item_code: string, default_unit_id: int, unit_cost: float|null}>, units: array<int, array{value: int, label: string}>, technologies: array<int, array{value: int, label: string}>, mediaTypes: array<int, array{value: string, label: string}>}
     */
    private function detailOptions(Innovation $innovation): array
    {
        $lines = $innovation->formulas->flatMap->items;
        $linkedItemIds = $lines->pluck('inventory_item_id')->unique()->values();
        $linkedUnitIds = $lines->pluck('unit_id')->unique()->values();
        $linkedTechnologyIds = $innovation->innovationTechnologies->pluck('technology_id');

        return [
            'inventoryItems' => InventoryItem::query()
                ->where(fn (Builder $query) => $query->where('status', InventoryItemStatus::Active)->orWhereIn('id', $linkedItemIds))
                ->orderBy('name')
                ->get(['id', 'item_code', 'name', 'default_unit_id', 'unit_cost'])
                ->map(fn (InventoryItem $item): array => [
                    'value' => $item->id,
                    'label' => $item->name,
                    'item_code' => $item->item_code,
                    'default_unit_id' => $item->default_unit_id,
                    'unit_cost' => $item->unit_cost !== null ? (float) $item->unit_cost : null,
                ])
                ->all(),
            'units' => Unit::query()
                ->where(fn (Builder $query) => $query->where('is_active', true)->orWhereIn('id', $linkedUnitIds))
                ->orderBy('name')
                ->get(['id', 'name', 'symbol'])
                ->map(fn (Unit $unit): array => ['value' => $unit->id, 'label' => "{$unit->name} ({$unit->symbol})"])
                ->all(),
            'technologies' => Technology::query()
                ->where(fn (Builder $query) => $query->where('status', TechnologyStatus::Active)->orWhereIn('id', $linkedTechnologyIds))
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (Technology $technology): array => ['value' => $technology->id, 'label' => $technology->name])
                ->all(),
            'mediaTypes' => InnovationMediaType::options(),
        ];
    }
}
