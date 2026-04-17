<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class CreateRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:20|unique:roles,name',
            'permissions' => 'nullable|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'name.unique' => 'Role already exists',
        ];
    }
}
