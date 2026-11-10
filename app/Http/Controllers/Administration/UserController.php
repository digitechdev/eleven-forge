<?php

namespace App\Http\Controllers\Administration;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administration\StoreUserRequest;
use App\Http\Requests\Administration\UpdateUserRequest;
use App\Models\User;
use App\Support\PerPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * List users with search, role, and status filters.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'role', 'status']);
        $actor = $request->user();
        $actorIsSuperAdmin = $actor->hasRole(UserRole::SuperAdmin->value);

        return Inertia::render('administration/users/Index', [
            'users' => User::query()
                ->with('roles:id,name')
                ->withCount('createdInnovations')
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%"),
                ))
                ->when($filters['role'] ?? null, fn ($query, string $role) => $query->whereHas('roles', fn ($query) => $query->where('name', $role)))
                ->when(UserStatus::tryFrom((string) ($filters['status'] ?? '')), fn ($query, UserStatus $status) => $query->where('status', $status))
                ->orderBy('name')
                ->paginate(PerPage::fromRequest($request))
                ->withQueryString()
                ->through(function (User $user) use ($actor, $actorIsSuperAdmin): array {
                    $roleName = $user->getRoleNames()->first();
                    $isSelf = $user->is($actor);
                    $isEditable = $actorIsSuperAdmin || $roleName !== UserRole::SuperAdmin->value;

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $roleName,
                        'role_label' => $roleName ? self::roleLabel($roleName) : null,
                        'status' => $user->status->value,
                        'status_label' => $user->status->label(),
                        'innovations_count' => $user->created_innovations_count,
                        'updated_at' => $user->updated_at?->toIso8601String(),
                        'is_self' => $isSelf,
                        'can' => [
                            'update' => $isEditable,
                            'toggleStatus' => $isEditable && ! $isSelf,
                            'resetPassword' => $isEditable && ! $isSelf,
                        ],
                    ];
                }),
            'filters' => $filters,
            'roles' => Role::query()
                ->with('permissions:id,name')
                ->where('guard_name', 'web')
                ->orderBy('name')
                ->get()
                ->map(fn (Role $role): array => [
                    'value' => $role->name,
                    'label' => self::roleLabel($role->name),
                    'assignable' => $actorIsSuperAdmin || $role->name !== UserRole::SuperAdmin->value,
                    'permissions' => $role->permissions->pluck('name')->sort()->values()->all(),
                ]),
            'statuses' => array_map(
                fn (UserStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
                UserStatus::cases(),
            ),
        ]);
    }

    /**
     * Create an admin-provisioned, email-verified user with a single role.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $user = new User($request->safe()->only(['name', 'email', 'password']));
            $user->forceFill([
                'email_verified_at' => now(),
                'status' => $request->validated('status'),
                'role' => $request->validated('role'),
            ])->save();

            $user->syncRoles([$request->validated('role')]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return back();
    }

    /**
     * Update a user's profile, role, status, and (optionally) password.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        DB::transaction(function () use ($request, $user): void {
            $user->fill($request->safe()->only(['name', 'email']));

            if ($request->filled('password')) {
                $user->password = $request->validated('password');
            }

            $user->forceFill([
                'status' => $request->validated('status'),
                'role' => $request->validated('role'),
            ])->save();

            $user->syncRoles([$request->validated('role')]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }

    /**
     * Activate or deactivate a user. Users are never hard-deleted so ownership history is kept.
     */
    public function toggleStatus(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('delete', $user);

        abort_if(
            $user->hasRole(UserRole::SuperAdmin->value) && ! $request->user()->hasRole(UserRole::SuperAdmin->value),
            403,
        );

        $user->forceFill([
            'status' => $user->isActive() ? UserStatus::Inactive : UserStatus::Active,
        ])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $user->isActive() ? __('User activated.') : __('User deactivated.'),
        ]);

        return back();
    }

    /**
     * Replace a user's password with a one-time temporary password and show it to the admin once.
     * Admins change their own password from their profile settings instead.
     */
    public function temporaryPassword(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        abort_if($user->is($request->user()), 403);
        abort_if(
            $user->hasRole(UserRole::SuperAdmin->value) && ! $request->user()->hasRole(UserRole::SuperAdmin->value),
            403,
        );

        $temporaryPassword = sprintf('EF-%s-%s-%d', Str::upper(Str::random(4)), Str::upper(Str::random(4)), random_int(1000, 9999));

        $user->forceFill(['password' => $temporaryPassword])->save();

        Inertia::flash('temporaryPassword', ['userName' => $user->name, 'password' => $temporaryPassword]);

        return back();
    }

    /**
     * A readable label for a stored role key.
     */
    private static function roleLabel(string $roleName): string
    {
        return UserRole::tryFrom($roleName)?->label() ?? Str::headline(Str::lower($roleName));
    }
}
