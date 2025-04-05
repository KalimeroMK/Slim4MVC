<?php

declare(strict_types=1);

// src/Actions/Auth/RegisterActionInterface.php

namespace App\Interface\Auth;

use App\DTO\Auth\RegisterDTO;
use App\Models\User;

interface RegisterActionInterface
{
    public function execute(RegisterDTO $dto): User;
}
