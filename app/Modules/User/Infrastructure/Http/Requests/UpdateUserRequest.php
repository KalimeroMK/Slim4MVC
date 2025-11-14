<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|min:8|confirmed',
            'password_confirmation' => 'nullable|min:8|confirmed',
        ];
    }
}
