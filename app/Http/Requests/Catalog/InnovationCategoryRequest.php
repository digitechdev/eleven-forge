<?php

namespace App\Http\Requests\Catalog;

use App\Models\InnovationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class InnovationCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $category = $this->route('innovation_category');

        return $category instanceof InnovationCategory
            ? $this->user()->can('update', $category)
            : $this->user()->can('create', InnovationCategory::class);
    }

    /**
     * Derive the slug from the name when it is left blank, and default the display order.
     */
    protected function prepareForValidation(): void
    {
        $slug = filled($this->input('slug')) ? $this->input('slug') : $this->input('name');

        $this->merge([
            'slug' => Str::slug((string) $slug),
            'sort_order' => $this->input('sort_order') ?? 0,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('innovation_category');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(InnovationCategory::class)->ignore($category)],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(InnovationCategory::class)->ignore($category)],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
