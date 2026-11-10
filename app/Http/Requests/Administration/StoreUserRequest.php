<?php

namespace App\Http\Requests\Administration;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => self::roleRules($this->user()),
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * Rules for a role assignment: an existing web role, and Super Admin only when the actor is one.
     *
     * @return array<int, mixed>
     */
    public static function roleRules(User $actor): array
    {
        return [
            'required',
            'string',
            Rule::exists(Role::class, 'name')->where('guard_name', 'web'),
            function (string $attribute, mixed $value, Closure $fail) use ($actor): void {
                if ($value === UserRole::SuperAdmin->value && ! $actor->hasRole(UserRole::SuperAdmin->value)) {
                    $fail(__('Only a Super Admin can assign the Super Admin role.'));
                }
            },
        ];
    }
}
