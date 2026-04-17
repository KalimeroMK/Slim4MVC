<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class CreateUserRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
            'roles' => 'nullable|array',
        ];
    }
}
