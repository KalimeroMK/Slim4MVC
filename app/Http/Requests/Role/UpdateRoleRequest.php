<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:20',
        ];
    }

}
