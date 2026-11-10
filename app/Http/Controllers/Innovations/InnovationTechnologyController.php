<?php

namespace App\Http\Controllers\Innovations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Innovations\InnovationTechnologyRequest;
use App\Models\Innovation;
use App\Models\InnovationTechnology;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class InnovationTechnologyController extends Controller
{
    /**
     * Link a technology to the innovation.
     */
    public function store(InnovationTechnologyRequest $request, Innovation $innovation): RedirectResponse
    {
        $innovation->innovationTechnologies()->create($request->validated());
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology linked.')]);

        return back();
    }

    /**
     * Update a technology link's version and purpose.
     */
    public function update(InnovationTechnologyRequest $request, Innovation $innovation, InnovationTechnology $innovationTechnology): RedirectResponse
    {
        $innovationTechnology->update($request->validated());
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology updated.')]);

        return back();
    }

    /**
     * Unlink a technology from the innovation.
     */
    public function destroy(Request $request, Innovation $innovation, InnovationTechnology $innovationTechnology): RedirectResponse
    {
        Gate::authorize('delete', $innovationTechnology);

        $innovationTechnology->delete();
        $innovation->forceFill(['updated_by' => $request->user()->id])->touch();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology removed.')]);

        return back();
    }
}
