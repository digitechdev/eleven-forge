<?php

namespace App\Http\Requests\Innovations;

use App\Enums\InnovationStatus;
use App\Enums\InnovationType;
use App\Enums\InnovationVisibility;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InnovationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $innovation = $this->route('innovation');

        return $innovation instanceof Innovation
            ? $this->user()->can('update', $innovation)
            : $this->user()->can('create', Innovation::class);
    }

    /**
     * Drop blank entries from the feature and benefit lists before validating.
     */
    protected function prepareForValidation(): void
    {
        foreach (['key_features', 'benefits'] as $list) {
            if (is_array($this->input($list))) {
                $this->merge([$list => array_values(array_filter(
                    array_map(fn (mixed $entry): mixed => is_string($entry) ? trim($entry) : $entry, $this->input($list)),
                    fn (mixed $entry): bool => filled($entry),
                ))]);
            }
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Code and slug uniqueness deliberately includes soft-deleted innovations, matching the database indexes.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $innovation = $this->route('innovation');
        $currentCategoryId = $innovation instanceof Innovation ? $innovation->category_id : null;

        return [
            'innovation_code' => ['nullable', 'string', 'max:50', Rule::unique(Innovation::class)->ignore($innovation)],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique(Innovation::class)->ignore($innovation)],
            'category_id' => ['required', 'integer', Rule::exists(InnovationCategory::class, 'id')->where(
                fn (Builder $query) => $query->where('is_active', true)->when($currentCategoryId, fn (Builder $query, int $id) => $query->orWhere('id', $id)),
            )],
            'type' => ['required', Rule::enum(InnovationType::class)],
            'tagline' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:20000'],
            'problem_statement' => ['nullable', 'string', 'max:10000'],
            'solution_description' => ['nullable', 'string', 'max:10000'],
            'key_features' => ['nullable', 'array', 'max:30'],
            'key_features.*' => ['string', 'max:500'],
            'benefits' => ['nullable', 'array', 'max:30'],
            'benefits.*' => ['string', 'max:500'],
            'status' => ['required', Rule::enum(InnovationStatus::class)],
            'visibility' => ['required', Rule::enum(InnovationVisibility::class)],
            'developed_at' => ['nullable', 'date'],
            'is_featured' => ['required', 'boolean'],
            'markup_percentage' => ['nullable', 'numeric', 'min:0', 'max:999.99', 'decimal:0,2'],
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
            'innovation_code.unique' => __('This code is already used by another innovation (archived innovations included).'),
            'slug.unique' => __('This slug is already used by another innovation (archived innovations included).'),
            'markup_percentage.max' => __('Markup may not be more than 999.99%.'),
        ];
    }
}
