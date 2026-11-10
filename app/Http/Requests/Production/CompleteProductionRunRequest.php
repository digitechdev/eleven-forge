<?php

namespace App\Http\Requests\Production;

use App\Models\BuildOrder;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteProductionRunRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $order = $this->route('buildOrder');

        return $order instanceof BuildOrder && $this->user()->can('complete', $order);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * The remaining-quantity check runs under a lock in the production run service.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'output_quantity' => ['required', 'numeric', 'gt:0', 'max:99999999999', 'decimal:0,3'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
