<?php

namespace App\Http\Requests\Administration;

use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine if the user is authorized to make this request. Only a Super Admin may edit a Super Admin.
     */
    public function authorize(): bool
    {
        $target = $this->targetUser();

        return $this->user()->can('update', $target)
            && ($this->user()->hasRole(UserRole::SuperAdmin->value) || ! $target->hasRole(UserRole::SuperAdmin->value));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->targetUser()->id),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'role' => StoreUserRequest::roleRules($this->user()),
            'status' => ['required', Rule::enum(UserStatus::class)],
        ];
    }

    /**
     * Stop users from changing their own role or deactivating themselves.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->targetUser()->is($this->user())) {
                    return;
                }

                if (! $validator->errors()->has('role') && ! $this->user()->hasRole((string) $this->input('role'))) {
                    $validator->errors()->add('role', __('You cannot change your own role.'));
                }

                if (! $validator->errors()->has('status') && $this->input('status') !== UserStatus::Active->value) {
                    $validator->errors()->add('status', __('You cannot deactivate your own account.'));
                }
            },
        ];
    }

    /**
     * The user being edited.
     */
    public function targetUser(): User
    {
        /** @var User $user */
        $user = $this->route('user');

        return $user;
    }
}
