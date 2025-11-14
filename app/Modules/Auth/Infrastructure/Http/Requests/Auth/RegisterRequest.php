<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Requests\Auth;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}

