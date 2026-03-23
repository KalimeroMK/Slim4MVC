<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Requests;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:20',
        ];
    }
}
