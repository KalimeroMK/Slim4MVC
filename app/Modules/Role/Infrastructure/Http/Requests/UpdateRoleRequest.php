<?php

declare(strict_types=1);

namespace App\Modules\Role\Infrastructure\Http\Requests;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

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
