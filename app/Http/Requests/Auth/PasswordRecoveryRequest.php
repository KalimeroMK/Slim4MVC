<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\FormRequest;

class PasswordRecoveryRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }

    protected function messages(): array
    {
        return [
            'email.exists' => 'Email not found in our system',
        ];
    }
}
