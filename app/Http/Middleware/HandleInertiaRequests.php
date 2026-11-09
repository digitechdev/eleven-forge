<?php

namespace App\Http\Middleware;

use App\Enums\AccessPermission;
use App\Enums\UserRole;
use App\Models\Innovation;
use App\Models\InnovationCategory;
use App\Models\InventoryItem;
use App\Models\ItemCategory;
use App\Models\Technology;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'access' => fn () => $request->user() ? $this->access($request->user()) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * What the signed-in user may do, used to build permission-aware navigation.
     * The backend still authorizes every request; this only shapes the UI.
     *
     * @return array{role: string|null, can: array<string, bool>}
     */
    private function access(User $user): array
    {
        $gate = Gate::forUser($user);
        $roleName = $user->getRoleNames()->first();

        return [
            'role' => $roleName ? (UserRole::tryFrom($roleName)?->label() ?? $roleName) : null,
            'can' => [
                'viewInnovations' => $gate->allows('viewAny', Innovation::class),
                'viewInnovationCategories' => $gate->allows('viewAny', InnovationCategory::class),
                'viewInventoryItems' => $gate->allows('viewAny', InventoryItem::class),
                'viewItemCategories' => $gate->allows('viewAny', ItemCategory::class),
                'viewUnits' => $gate->allows('viewAny', Unit::class),
                'viewTechnologies' => $gate->allows('viewAny', Technology::class),
                'manageUsers' => $gate->allows('viewAny', User::class),
                'manageRoles' => $gate->allows(AccessPermission::RoleManage->value),
            ],
        ];
    }
}
