<?php

namespace App\Http\Requests\Administration;

use App\Enums\AccessPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(AccessPermission::RoleManage->value);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:125', 'regex:/^[A-Za-z][A-Za-z0-9 _-]*$/'],
        ];
    }

    /**
     * Reject a name that maps to an existing role key.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                if (Role::query()->where('name', $this->roleKey())->where('guard_name', 'web')->exists()) {
                    $validator->errors()->add('name', __('A role with this name already exists.'));
                }
            },
        ];
    }

    /**
     * The stored role key, e.g. "Project Reviewer" becomes "PROJECT_REVIEWER".
     */
    public function roleKey(): string
    {
        return Str::upper(Str::slug((string) $this->input('name'), '_'));
    }
}
