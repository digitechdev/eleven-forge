<?php

namespace App\Http\Requests\Innovations;

use App\Enums\TechnologyStatus;
use App\Models\Innovation;
use App\Models\InnovationTechnology;
use App\Models\Technology;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InnovationTechnologyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $link = $this->route('innovationTechnology');

        return $link instanceof InnovationTechnology
            ? $this->user()->can('update', $link)
            : $this->user()->can('create', [InnovationTechnology::class, $this->route('innovation')]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * New links must use active technologies; an existing link may keep the technology it already has.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Innovation $innovation */
        $innovation = $this->route('innovation');
        $link = $this->route('innovationTechnology');
        $currentTechnologyId = $link instanceof InnovationTechnology ? $link->technology_id : null;

        return [
            'technology_id' => [
                'required',
                'integer',
                Rule::exists(Technology::class, 'id')->where(fn (Builder $query) => $query
                    ->where('status', TechnologyStatus::Active->value)
                    ->when($currentTechnologyId, fn (Builder $query, int $id) => $query->orWhere('id', $id))),
                Rule::unique(InnovationTechnology::class)->where('innovation_id', $innovation->id)->ignore($link),
            ],
            'version' => ['nullable', 'string', 'max:50'],
            'purpose' => ['nullable', 'string', 'max:2000'],
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
            'technology_id.unique' => __('This technology is already linked to the innovation.'),
        ];
    }
}
