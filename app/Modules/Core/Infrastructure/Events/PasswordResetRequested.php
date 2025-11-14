<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Events;

use App\Modules\User\Infrastructure\Models\User;

class PasswordResetRequested extends Event
{
    public function __construct(
        public readonly User $user,
        public readonly string $token
    ) {}
}
