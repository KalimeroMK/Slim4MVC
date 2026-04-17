<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $id = $this->routeParam('id');

        return [
            'name' => 'required|string|max:20|unique:roles,name,'.$id,
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
            'name.unique' => 'Role name already taken',
        ];
    }
}
