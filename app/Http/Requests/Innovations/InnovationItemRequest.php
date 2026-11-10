<?php

namespace App\Http\Requests\Innovations;

use App\Enums\InventoryItemStatus;
use App\Models\Innovation;
use App\Models\InnovationItem;
use App\Models\InventoryItem;
use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InnovationItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $line = $this->route('innovationItem');

        return $line instanceof InnovationItem
            ? $this->user()->can('update', $line)
            : $this->user()->can('create', [InnovationItem::class, $this->route('innovation')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * New lines must use active items and units; an existing line may keep the item and unit it already has.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Innovation $innovation */
        $innovation = $this->route('innovation');
        $line = $this->route('innovationItem');
        $currentItemId = $line instanceof InnovationItem ? $line->inventory_item_id : null;
        $currentUnitId = $line instanceof InnovationItem ? $line->unit_id : null;

        return [
            'inventory_item_id' => [
                'required',
                'integer',
                Rule::exists(InventoryItem::class, 'id')->withoutTrashed()->where(fn (Builder $query) => $query
                    ->where('status', InventoryItemStatus::Active->value)
                    ->when($currentItemId, fn (Builder $query, int $id) => $query->orWhere('id', $id))),
                Rule::unique(InnovationItem::class)->where('innovation_id', $innovation->id)->ignore($line),
            ],
            'unit_id' => ['nullable', 'integer', Rule::exists(Unit::class, 'id')->where(fn (Builder $query) => $query
                ->where('is_active', true)
                ->when($currentUnitId, fn (Builder $query, int $id) => $query->orWhere('id', $id)))],
            'quantity_required' => ['required', 'numeric', 'gt:0', 'max:99999999999'],
            'usage_description' => ['nullable', 'string', 'max:2000'],
            'specification_notes' => ['nullable', 'string', 'max:2000'],
            'is_optional' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'inventory_item_id.unique' => __('This item is already in the bill of materials. Edit the existing line instead.'),
        ];
    }
}
