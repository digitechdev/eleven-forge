<?php

namespace App\Http\Controllers\Catalog;

use App\Enums\TechnologyCategory;
use App\Enums\TechnologyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\TechnologyRequest;
use App\Models\InnovationTechnology;
use App\Models\Technology;
use App\Support\PerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TechnologyController extends Controller
{
    /**
     * List technologies with the innovations that use them, filtered by search, category and status.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category', 'status']);

        return Inertia::render('technologies/Index', [
            'technologies' => Technology::query()
                ->withCount('innovations')
                ->with(['innovationTechnologies' => fn ($query) => $query
                    ->whereHas('innovation')
                    ->with('innovation:id,innovation_code,name')])
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('description', "%{$search}%"),
                ))
                ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->orderBy('name')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(fn (Technology $technology): array => [
                    'id' => $technology->id,
                    'name' => $technology->name,
                    'category' => $technology->category->value,
                    'category_label' => $technology->category->label(),
                    'description' => $technology->description,
                    'website' => $technology->website,
                    'status' => $technology->status->value,
                    'innovations_count' => $technology->innovations_count,
                    'innovations' => $technology->innovationTechnologies
                        ->sortBy(fn (InnovationTechnology $link): string => $link->innovation->name)
                        ->map(fn (InnovationTechnology $link): array => [
                            'id' => $link->innovation->id,
                            'code' => $link->innovation->innovation_code,
                            'name' => $link->innovation->name,
                            'version' => $link->version,
                            'purpose' => $link->purpose,
                        ])->values()->all(),
                ]),
            'filters' => $filters,
            'categoryOptions' => array_map(
                fn (TechnologyCategory $category): array => ['value' => $category->value, 'label' => $category->label()],
                TechnologyCategory::cases(),
            ),
            'statusOptions' => array_map(
                fn (TechnologyStatus $status): array => ['value' => $status->value, 'label' => $status->name],
                TechnologyStatus::cases(),
            ),
            'can' => [
                'manage' => Gate::allows('create', Technology::class),
            ],
        ]);
    }

    /**
     * Create a technology.
     */
    public function store(TechnologyRequest $request): RedirectResponse
    {
        Technology::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology created.')]);

        return back();
    }

    /**
     * Update a technology.
     */
    public function update(TechnologyRequest $request, Technology $technology): RedirectResponse
    {
        $technology->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology updated.')]);

        return back();
    }

    /**
     * Delete an unused technology. Technologies linked to innovations must be deprecated instead.
     */
    public function destroy(Technology $technology): RedirectResponse
    {
        Gate::authorize('delete', $technology);

        if ($technology->isInUse()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This technology is used by innovations. Deprecate it instead.')]);

            return back();
        }

        $technology->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Technology deleted.')]);

        return back();
    }
}
