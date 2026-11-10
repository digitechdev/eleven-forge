<?php

namespace App\Http\Requests\Production;

use App\Enums\FormulaStatus;
use App\Models\BuildOrder;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBuildOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', BuildOrder::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Orders are built from the innovation's active formula and consume parts at an active location.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'innovation_id' => [
                'required',
                'integer',
                Rule::exists(Innovation::class, 'id')->withoutTrashed(),
                Rule::exists(InnovationFormula::class, 'innovation_id')->where('status', FormulaStatus::Active->value),
            ],
            'location_id' => ['required', 'integer', Rule::exists(Location::class, 'id')->where('is_active', true)],
            'planned_quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999', 'decimal:0,3'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'plan' => ['required', 'boolean'],
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
            'innovation_id.exists' => __('Choose an innovation with an active formula.'),
            'location_id.exists' => __('Choose an active location.'),
        ];
    }
}
