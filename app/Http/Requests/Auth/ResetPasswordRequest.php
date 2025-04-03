<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'token' => 'required|string|exists:users,password_reset_token',
            'password' => 'required|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    protected function messages(): array
    {
        return [
            'token.exists' => 'Invalid or expired reset token',
        ];
    }
}
