<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\Interfaces\Auth;

use App\Modules\Auth\Application\DTOs\Auth\LoginDTO;

interface LoginActionInterface
{
    /**
     * Execute login action.
     *
     * @param LoginDTO $dto
     * @return array<string, mixed>
     */
    public function execute(LoginDTO $dto): array;
}

