<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;

class PasswordResetRequested extends Event
{
    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {}
}

