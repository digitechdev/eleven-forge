<?php

namespace App\Http\Requests\Catalog;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyStatus;
use App\Models\Technology;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TechnologyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $technology = $this->route('technology');

        return $technology instanceof Technology
            ? $this->user()->can('update', $technology)
            : $this->user()->can('create', Technology::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Technology::class)->ignore($this->route('technology'))],
            'category' => ['required', Rule::enum(TechnologyCategory::class)],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'status' => ['required', Rule::enum(TechnologyStatus::class)],
        ];
    }
}
