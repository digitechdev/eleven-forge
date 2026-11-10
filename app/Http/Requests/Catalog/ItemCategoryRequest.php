<?php

namespace App\Http\Requests\Catalog;

use App\Models\ItemCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('item_category');

        return $category instanceof ItemCategory
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', ItemCategory::class);
    }

    /**
     * Default the display order when it is left blank.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['sort_order' => $this->input('sort_order') ?? 0]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('item_category');
        $parentId = $this->input('parent_id');

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists(ItemCategory::class, 'id'),
                function (string $attribute, mixed $value, Closure $fail) use ($category): void {
                    if (! $category instanceof ItemCategory || $value === null) {
                        return;
                    }

                    if ((int) $value === $category->id || in_array((int) $value, $category->descendantIds(), true)) {
                        $fail(__('A category cannot be placed under itself or one of its subcategories.'));
                    }
                },
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(ItemCategory::class)
                    ->where(fn ($query) => $query->where('parent_id', $parentId))
                    ->ignore($category),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => __('A category with this name already exists under the same parent.')];
    }
}
