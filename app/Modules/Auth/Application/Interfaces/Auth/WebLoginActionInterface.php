<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;

interface WebLoginActionInterface
{
    /**
     * Execute web login action (session-based).
     */
    public function execute(LoginDTO $dto): void;
}
