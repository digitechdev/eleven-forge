<?php

namespace App\Http\Requests\Innovations;

use App\Models\InnovationFormula;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class InnovationFormulaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $formula = $this->route('formula');

        return $formula instanceof InnovationFormula && $this->user()->can('update', $formula);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'yield_quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
