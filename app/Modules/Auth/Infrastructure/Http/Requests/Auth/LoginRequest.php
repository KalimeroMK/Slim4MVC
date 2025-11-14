<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Requests\Auth;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
