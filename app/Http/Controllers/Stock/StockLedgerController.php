<?php

namespace App\Http\Controllers\Stock;

use App\Enums\StockTransactionType;
use App\Exceptions\StockPostingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Stock\PostStockTransactionRequest;
use App\Http\Requests\Stock\ReverseStockEntryRequest;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use App\Services\StockLedgerService;
use App\Support\PerPage;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class StockLedgerController extends Controller
{
    use ProvidesPostingOptions;

    /**
     * The read-only stock ledger, newest first, filterable by item, location, type, date range, and reference.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['item', 'location', 'type', 'from', 'to', 'reference']);
        $canPost = Gate::allows('create', StockLedgerEntry::class);

        return Inertia::render('inventory/ledger/Index', [
            'entries' => StockLedgerEntry::query()
                ->with([
                    'inventoryItem:id,item_code,name,default_unit_id,deleted_at',
                    'inventoryItem.defaultUnit:id,symbol',
                    'location:id,code,name',
                    'creator:id,name',
                    'reversal:id,entry_number,reversal_of_entry_id',
                    'reversalOf:id,entry_number',
                ])
                ->when($filters['item'] ?? null, fn (Builder $query, string $item) => $query->where('inventory_item_id', (int) $item))
                ->when($filters['location'] ?? null, fn (Builder $query, string $location) => $query->where('location_id', (int) $location))
                ->when(StockTransactionType::tryFrom((string) ($filters['type'] ?? '')), fn (Builder $query, StockTransactionType $type) => $query->where('transaction_type', $type))
                ->when($this->date($filters['from'] ?? null), fn (Builder $query, Carbon $from) => $query->where('posted_at', '>=', $from->startOfDay()))
                ->when($this->date($filters['to'] ?? null), fn (Builder $query, Carbon $to) => $query->where('posted_at', '<=', $to->endOfDay()))
                ->when($filters['reference'] ?? null, fn (Builder $query, string $reference) => $query->whereLike('reference_number', "%{$reference}%"))
                ->orderByDesc('posted_at')
                ->orderByDesc('id')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (StockLedgerEntry $entry): array => $this->entryData($entry, $canPost)),
            'filters' => $filters,
            'items' => InventoryItem::query()->withTrashed()->orderBy('name')->get(['id', 'item_code', 'name'])
                ->map(fn (InventoryItem $item): array => ['value' => $item->id, 'label' => "{$item->item_code} · {$item->name}"]),
            'locations' => $this->locationOptions(),
            'types' => StockTransactionType::options(),
            'posting' => $canPost ? fn (): array => $this->postingOptions() : null,
            'can' => [
                'post' => $canPost,
            ],
        ]);
    }

    /**
     * Post a manual stock transaction through the ledger service.
     */
    public function store(PostStockTransactionRequest $request, StockLedgerService $ledger): RedirectResponse
    {
        $type = $request->transactionType();
        $quantity = (float) $request->validated('quantity');
        $postedAt = Carbon::parse($request->validated('posted_at'));
        $unitCost = $request->validated('unit_cost');

        try {
            $entry = $ledger->post(
                InventoryItem::query()->findOrFail($request->integer('inventory_item_id')),
                Location::query()->findOrFail($request->integer('location_id')),
                $type,
                $request->isIncoming() ? $quantity : 0,
                $request->isIncoming() ? 0 : $quantity,
                $unitCost === null ? null : (float) $unitCost,
                $request->user(),
                [
                    'posted_at' => $postedAt->isToday() ? now() : $postedAt->startOfDay(),
                    'reference_number' => $request->validated('reference_number'),
                    'notes' => $request->validated('notes'),
                ],
            );
        } catch (StockPostingException $exception) {
            throw ValidationException::withMessages(['quantity' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':type posted as :entry.', ['type' => $type->label(), 'entry' => $entry->entry_number])]);

        return back();
    }

    /**
     * Correct an entry by posting its inverse. The original is never edited.
     */
    public function reverse(ReverseStockEntryRequest $request, StockLedgerEntry $entry, StockLedgerService $ledger): RedirectResponse
    {
        try {
            $reversal = $ledger->reverse($entry, $request->user(), $request->validated('reason'));
        } catch (StockPostingException $exception) {
            throw ValidationException::withMessages(['reason' => $exception->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __(':entry reversed by :reversal.', ['entry' => $entry->entry_number, 'reversal' => $reversal->entry_number])]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function entryData(StockLedgerEntry $entry, bool $canPost): array
    {
        return [
            'id' => $entry->id,
            'entry_number' => $entry->entry_number,
            'posted_at' => $entry->posted_at->toIso8601String(),
            'transaction_type' => $entry->transaction_type->value,
            'transaction_type_label' => $entry->transaction_type->label(),
            'item_id' => $entry->inventory_item_id,
            'item_code' => $entry->inventoryItem->item_code,
            'item_name' => $entry->inventoryItem->name,
            'item_is_deleted' => $entry->inventoryItem->trashed(),
            'unit_symbol' => $entry->inventoryItem->defaultUnit->symbol,
            'location_code' => $entry->location->code,
            'location_name' => $entry->location->name,
            'quantity_in' => (float) $entry->quantity_in,
            'quantity_out' => (float) $entry->quantity_out,
            'unit_cost' => (float) $entry->unit_cost,
            'total_value' => (float) $entry->total_value,
            'balance_after' => (float) $entry->balance_after,
            'reference_number' => $entry->reference_number,
            'created_by' => $entry->creator?->name,
            'notes' => $entry->notes,
            'reversed_by' => $entry->reversal?->entry_number,
            'reverses' => $entry->reversalOf?->entry_number,
            'can_reverse' => $canPost
                && $entry->reversal === null
                && $entry->transaction_type !== StockTransactionType::Reversal
                && $entry->production_run_id === null,
        ];
    }

    /**
     * A valid Y-m-d filter date, or null.
     */
    private function date(?string $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value) ?: null;
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
