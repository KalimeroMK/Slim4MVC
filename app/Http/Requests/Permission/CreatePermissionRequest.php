<?php

namespace App\Http\Requests\Role;

use App\Http\Requests\FormRequest;

class CreatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:roles,name|max:20',
        ];
    }

}
