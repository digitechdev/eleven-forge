<?php

namespace App\Http\Requests\Stock;

use App\Enums\InventoryItemStatus;
use App\Enums\StockTransactionType;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\StockLedgerEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A hand-posted stock movement: opening balance, receive, issue, return, or adjustment.
 */
class PostStockTransactionRequest extends FormRequest
{
    public const string INCREASE = 'increase';

    public const string DECREASE = 'decrease';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', StockLedgerEntry::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isAdjustment = $this->input('transaction_type') === StockTransactionType::Adjustment->value;
        $needsCost = in_array($this->input('transaction_type'), [StockTransactionType::OpeningBalance->value, StockTransactionType::Receive->value], true);

        return [
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists(InventoryItem::class, 'id')->where('status', InventoryItemStatus::Active->value)->whereNull('deleted_at'),
            ],
            'location_id' => ['required', 'integer', Rule::exists(Location::class, 'id')->where('is_active', true)],
            'transaction_type' => ['required', Rule::enum(StockTransactionType::class)->only(StockTransactionType::manual())],
            'direction' => [Rule::requiredIf($isAdjustment), 'nullable', Rule::in([self::INCREASE, self::DECREASE])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'unit_cost' => [Rule::requiredIf($needsCost), 'nullable', 'numeric', 'min:0', 'max:9999999999'],
            'posted_at' => ['required', 'date', 'before_or_equal:today'],
            'reference_number' => [Rule::requiredIf($isAdjustment), 'nullable', 'string', 'max:100'],
            'notes' => [Rule::requiredIf($isAdjustment), 'nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Quantities may not be more precise than the item's unit allows.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['inventory_item_id', 'quantity'])) {
                    return;
                }

                $decimalPlaces = (int) InventoryItem::query()->with('defaultUnit:id,decimal_places')
                    ->find($this->integer('inventory_item_id'))?->defaultUnit?->decimal_places;
                $fraction = explode('.', rtrim(rtrim((string) $this->input('quantity'), '0'), '.'))[1] ?? '';

                if (strlen($fraction) > $decimalPlaces) {
                    $validator->errors()->add('quantity', $decimalPlaces === 0
                        ? __('This item is counted in whole units.')
                        : __('Use at most :places decimal places for this item.', ['places' => $decimalPlaces]));
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inventory_item_id.exists' => __('Choose an active item.'),
            'location_id.exists' => __('Choose an active location. Inactive locations cannot receive new postings.'),
            'posted_at.before_or_equal' => __('The posting date cannot be in the future.'),
            'notes.required' => __('Give a reason for the adjustment.'),
            'reference_number.required' => __('Adjustments need a reference number.'),
            'unit_cost.required' => __('Enter the unit cost for this transaction.'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'inventory_item_id' => 'item',
            'location_id' => 'location',
            'transaction_type' => 'transaction type',
            'posted_at' => 'posting date',
        ];
    }

    /**
     * The transaction type being posted.
     */
    public function transactionType(): StockTransactionType
    {
        return StockTransactionType::from($this->validated('transaction_type'));
    }

    /**
     * Whether the movement adds stock (otherwise it takes stock out).
     */
    public function isIncoming(): bool
    {
        return match ($this->transactionType()) {
            StockTransactionType::Issue => false,
            StockTransactionType::Adjustment => $this->validated('direction') === self::INCREASE,
            default => true,
        };
    }
}
