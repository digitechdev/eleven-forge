<?php

namespace App\Http\Requests\Administration;

use App\Enums\AccessPermission;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Permission;

class UpdateRolePermissionsRequest extends FormRequest
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
            'permissions' => ['present', 'array'],
            'permissions.*' => [
                'integer',
                'distinct',
                Rule::exists('permissions', 'id')->where('guard_name', 'web'),
            ],
        ];
    }

    /**
     * Role management stays reserved for Super Admin.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $grantsRoleManagement = Permission::query()
                    ->whereIn('id', (array) $this->input('permissions', []))
                    ->where('name', AccessPermission::RoleManage->value)
                    ->exists();

                if ($grantsRoleManagement) {
                    $validator->errors()->add('permissions', __('Role management is reserved for Super Admin.'));
                }
            },
        ];
    }
}
