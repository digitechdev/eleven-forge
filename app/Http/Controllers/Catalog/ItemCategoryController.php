<?php

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Catalog\ItemCategoryRequest;
use App\Models\ItemCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ItemCategoryController extends Controller
{
    /**
     * Show the category hierarchy in tree order, narrowed by search and active-state filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status']);
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $status = $filters['status'] ?? null;

        $tree = $this->flattenTree(
            ItemCategory::query()
                ->withCount(['inventoryItems', 'children'])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
        );

        $categories = array_values(array_filter($tree, fn (array $category): bool => match (true) {
            $status === 'active' && ! $category['is_active'] => false,
            $status === 'inactive' && $category['is_active'] => false,
            $search !== '' => Str::contains(Str::lower($category['name'].' '.$category['description']), $search),
            default => true,
        }));

        return Inertia::render('inventory/categories/Index', [
            'categories' => $categories,
            'parentOptions' => array_map(fn (array $category): array => [
                'id' => $category['id'],
                'parent_id' => $category['parent_id'],
                'name' => $category['name'],
                'depth' => $category['depth'],
            ], $tree),
            'filters' => $filters,
            'can' => [
                'manage' => Gate::allows('create', ItemCategory::class),
            ],
        ]);
    }

    /**
     * Create an item category.
     */
    public function store(ItemCategoryRequest $request): RedirectResponse
    {
        ItemCategory::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category created.')]);

        return back();
    }

    /**
     * Update an item category.
     */
    public function update(ItemCategoryRequest $request, ItemCategory $itemCategory): RedirectResponse
    {
        $itemCategory->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category updated.')]);

        return back();
    }

    /**
     * Delete an empty category. Categories with items or subcategories must be deactivated instead.
     */
    public function destroy(ItemCategory $itemCategory): RedirectResponse
    {
        Gate::authorize('delete', $itemCategory);

        if ($itemCategory->isInUse()) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('This category has items or subcategories. Deactivate it instead.')]);

            return back();
        }

        $itemCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Category deleted.')]);

        return back();
    }

    /**
     * Flatten pre-sorted categories into depth-first tree order.
     *
     * @param  Collection<int, ItemCategory>  $categories
     * @return list<array{id: int, parent_id: int|null, name: string, description: string|null, is_active: bool, sort_order: int, depth: int, path: string, items_count: int, children_count: int}>
     */
    private function flattenTree(Collection $categories): array
    {
        $childrenByParent = $categories->groupBy(fn (ItemCategory $category): int => $category->parent_id ?? 0);
        $rows = [];

        $walk = function (int $parentId, int $depth, array $ancestors) use (&$walk, &$rows, $childrenByParent): void {
            foreach ($childrenByParent->get($parentId, []) as $category) {
                $rows[] = [
                    'id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'is_active' => $category->is_active,
                    'sort_order' => $category->sort_order,
                    'depth' => $depth,
                    'path' => implode(' / ', [...$ancestors, $category->name]),
                    'items_count' => $category->inventory_items_count,
                    'children_count' => $category->children_count,
                ];

                $walk($category->id, $depth + 1, [...$ancestors, $category->name]);
            }
        };

        $walk(0, 0, []);

        return $rows;
    }
}
