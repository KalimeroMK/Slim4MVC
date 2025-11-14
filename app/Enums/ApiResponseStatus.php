<?php

declare(strict_types=1);

namespace App\Enums;

enum ApiResponseStatus: string
{
    case SUCCESS = 'success';
    case ERROR = 'error';

    public function getValue(): string
    {
        return $this->value;
    }
}
