<?php

namespace App\Http\Requests\Inventory;

use App\Enums\InventoryItemStatus;
use App\Enums\InventoryItemType;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class InventoryItemRequest extends FormRequest
{
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
            'quantity_on_hand' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'minimum_stock' => ['required', 'numeric', 'min:0', 'max:99999999999'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'status' => ['required', Rule::enum(InventoryItemStatus::class)],
        ];
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
