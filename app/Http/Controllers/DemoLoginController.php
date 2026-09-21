<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoLoginController extends Controller
{
    public function __invoke(Request $request, string $role): RedirectResponse
    {
        abort_unless(app()->environment(['local', 'testing']), 404);

        $selectedRole = UserRole::tryFrom($role);

        abort_if($selectedRole === null, 404);

        $user = User::query()
            ->where('email', strtolower($selectedRole->value).'@elevenforge.test')
            ->firstOrFail();

        abort_unless($user->isActive() && $user->hasRole($selectedRole->value), 403);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
