<?php

declare(strict_types=1);

namespace App\Support;

class SessionHelper
{
    public static function flashErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    public static function flashOldInput(array $data): void
    {
        $_SESSION['old'] = $data;
    }

    public static function clearFlash(): void
    {
        unset($_SESSION['errors'], $_SESSION['old']);
    }
}
