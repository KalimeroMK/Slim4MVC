<?php

declare(strict_types=1);

namespace App\Modules\Permission\Infrastructure\Http\Requests;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdatePermissionRequest extends FormRequest
{
    protected function rules(): array
    {
        $id = $this->routeParam('id');

        return [
            'name' => "required|string|unique:permissions,name,{$id}|max:20",
        ];
    }
}
