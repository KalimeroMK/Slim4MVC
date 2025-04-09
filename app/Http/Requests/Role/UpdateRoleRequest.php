<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Http\Requests\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
            'permissions' => 'nullable|array',
        ];
    }
}
