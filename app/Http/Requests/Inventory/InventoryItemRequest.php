<?php

namespace App\Http\Requests\Inventory;

use App\Enums\AccessPermission;
use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Location;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

/**
 * Item details. Quantity on hand is never accepted: stock only changes through the ledger.
 * Creating may post optional opening stock; editing may explain a unit cost change.
 */
class InventoryItemRequest extends FormRequest
{
    /**
     * Fields that are not item attributes.
     */
    public const array NON_ITEM_FIELDS = ['opening_quantity', 'opening_location_id', 'opening_unit_cost', 'cost_change_reason'];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $item = $this->route('item');

        return $item instanceof InventoryItem
            ? $this->user()->can('update', $item)
            : $this->user()->can('create', InventoryItem::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $item = $this->route('item');
        $item = $item instanceof InventoryItem ? $item : null;
        $isCreate = $item === null;

        return [
            'item_code' => ['nullable', 'string', 'max:50', Rule::unique(InventoryItem::class)->ignore($item)],
            'name' => ['required', 'string', 'max:255'],
            'item_type' => ['required', Rule::enum(InventoryItemType::class)],
            'category_id' => ['required', 'integer', $this->activeOrCurrent(ItemCategory::class, $item?->category_id)],
            'default_unit_id' => ['required', 'integer', $this->activeOrCurrent(Unit::class, $item?->default_unit_id)],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'part_number' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'specification' => ['nullable', 'string', 'max:10000'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'reorder_level' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'status' => ['required', Rule::enum(InventoryItemStatus::class)],
            'opening_quantity' => $isCreate ? ['nullable', 'numeric', 'min:0', 'max:99999999999'] : ['exclude'],
            'opening_location_id' => $isCreate
                ? [Rule::requiredIf(fn (): bool => (float) $this->input('opening_quantity') > 0), 'nullable', 'integer', Rule::exists(Location::class, 'id')->where('is_active', true)]
                : ['exclude'],
            'opening_unit_cost' => $isCreate ? ['nullable', 'numeric', 'min:0', 'max:999999999999'] : ['exclude'],
            'cost_change_reason' => $isCreate ? ['exclude'] : ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Opening stock is a stock posting, so it needs the stock posting permission as well.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((float) $this->input('opening_quantity') > 0 && ! $this->user()->can(AccessPermission::StockPost->value)) {
                    $validator->errors()->add('opening_quantity', __('You are not allowed to post stock. Create the item without opening stock.'));
                }
            },
        ];
    }

    /**
     * Whether opening stock should be posted for the new item.
     */
    public function hasOpeningStock(): bool
    {
        return (float) $this->validated('opening_quantity', 0) > 0;
    }

    /**
     * The validated item attributes only.
     *
     * @return array<string, mixed>
     */
    public function itemAttributes(): array
    {
        return $this->safe()->except(self::NON_ITEM_FIELDS);
    }

    /**
     * The record must exist and be active, unless it is the one the item already uses.
     *
     * @param  class-string<ItemCategory|Unit>  $model
     */
    private function activeOrCurrent(string $model, ?int $currentId): Exists
    {
        return Rule::exists($model, 'id')->where(fn (Builder $query) => $query->where(
            fn (Builder $query) => $query->where('is_active', true)->when($currentId, fn (Builder $query) => $query->orWhere('id', $currentId)),
        ));
    }
}
