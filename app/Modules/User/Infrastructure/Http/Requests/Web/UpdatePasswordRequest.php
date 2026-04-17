<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Http\Requests\Web;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;

class UpdatePasswordRequest extends FormRequest
{
    protected function rules(): array
    {
        return [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }
}
