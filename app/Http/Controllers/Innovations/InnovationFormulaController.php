<?php

namespace App\Http\Controllers\Innovations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationFormulaRequest;
use App\Models\Innovation;
use App\Models\InnovationFormula;
use App\Services\InnovationFormulaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Versions of an innovation's bill of materials: start a new version, adjust a draft's yield, and activate it.
 */
class InnovationFormulaController extends Controller
{
    public function __construct(private InnovationFormulaService $formulas) {}

    /**
     * Start a new draft version, copying the lines of the chosen source version.
     */
    public function store(Request $request, Innovation $innovation): RedirectResponse
    {
        Gate::authorize('create', [InnovationFormula::class, $innovation]);

        $validated = $request->validate([
            'source_formula_id' => ['nullable', 'integer', Rule::exists(InnovationFormula::class, 'id')->where('innovation_id', $innovation->id)],
        ]);

        $source = isset($validated['source_formula_id']) ? $innovation->formulas()->whereKey($validated['source_formula_id'])->firstOrFail() : null;
        $formula = $this->formulas->createVersion($innovation, $source);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version :version created as a draft.', ['version' => $formula->version])]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $formula->id]);
    }

    /**
     * Change the yield and notes of an editable formula.
     */
    public function update(InnovationFormulaRequest $request, Innovation $innovation, InnovationFormula $formula): RedirectResponse
    {
        $formula->update($request->validated());
        $this->formulas->refreshCosting($formula);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Formula updated.')]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $formula->id]);
    }

    /**
     * Make a draft the active formula, archiving the previous active version.
     */
    public function activate(Request $request, Innovation $innovation, InnovationFormula $formula): RedirectResponse
    {
        Gate::authorize('activate', $formula);

        $this->formulas->activate($formula);
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Version :version is now active.', ['version' => $formula->version])]);

        return to_route('innovations.show', ['innovation' => $innovation, 'formula' => $formula->id]);
    }
}
