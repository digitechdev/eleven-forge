<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\InnovationCategoryRequest;
use App\Models\InnovationCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InnovationCategoryController extends Controller
{
    /**
     * List innovation categories with search and active-state filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);

        return Inertia::render('innovation-categories/Index', [
            'categories' => InnovationCategory::query()
                ->withCount('innovations')
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('slug', "%{$search}%")
                        ->orWhereLike('description', "%{$search}%"),
                ))
                ->when(($filters['status'] ?? null) === 'active', fn ($query) => $query->where('is_active', true))
                ->when(($filters['status'] ?? null) === 'inactive', fn ($query) => $query->where('is_active', false))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString()
                ->through(fn (InnovationCategory $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'innovations_count' => $category->innovations_count,
                ]),
            'filters' => $filters,
            'can' => [
                'manage' => Gate::allows('create', InnovationCategory::class),
            ],
        ]);
    }

    /**
     * Create an innovation category.
     */
    public function store(InnovationCategoryRequest $request): RedirectResponse
    {
        InnovationCategory::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    /**
     * Update an innovation category.
     */
    public function update(InnovationCategoryRequest $request, InnovationCategory $innovationCategory): RedirectResponse
    {
        $innovationCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    /**
     * Delete an unused category. Categories that classify innovations must be deactivated instead.
     */
    public function destroy(InnovationCategory $innovationCategory): RedirectResponse
    {
        Gate::authorize('delete', $innovationCategory);

        if ($innovationCategory->isInUse()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This category has innovations. Deactivate it instead.')]);

            return back();
        }

        $innovationCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }
}
