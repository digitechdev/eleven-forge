<?php

namespace App\Http\Requests\Stock;

use App\Models\StockLedgerEntry;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReverseStockEntryRequest extends FormRequest
{
    /**
     * Only entries that are still reversible, by users who may post stock.
     */
    public function authorize(): bool
    {
        $entry = $this->route('entry');

        return $entry instanceof StockLedgerEntry && $this->user()->can('reverse', $entry);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
