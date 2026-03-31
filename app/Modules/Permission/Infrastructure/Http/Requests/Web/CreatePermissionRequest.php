<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name'  => 'required|string|max:50|unique:permissions,name',
            'roles' => 'nullable|array',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'name.required' => 'Permission name is required',
            'name.unique'   => 'Permission already exists',
        ];
    }
}
