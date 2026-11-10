<?php

namespace App\Http\Requests\Inventory;

use App\Models\Unit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UnitRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $unit = $this->route('unit');

        return $unit instanceof Unit
            ? $this->user()->can('update', $unit)
            : $this->user()->can('create', Unit::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $unit = $this->route('unit');

        return [
            'name' => ['required', 'string', 'max:100', Rule::unique(Unit::class)->ignore($unit)],
            'symbol' => ['required', 'string', 'max:20', Rule::unique(Unit::class)->ignore($unit)],
            'decimal_places' => ['required', 'integer', 'min:0', 'max:3'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
