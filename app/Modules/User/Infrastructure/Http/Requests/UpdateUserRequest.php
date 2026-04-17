<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Requests;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdateUserRequest extends FormRequest
{
    protected function rules(): array
    {
        $id = $this->routeParam('id');

        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email,'.$id,
            'password' => 'nullable|string|min:8|confirmed',
            'password_confirmation' => 'nullable|string',
        ];
    }
}
