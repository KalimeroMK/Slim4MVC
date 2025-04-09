<?php

declare(strict_types=1);

namespace App\Http\Requests\Permission;

use App\Http\Requests\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
        ];
    }
}
