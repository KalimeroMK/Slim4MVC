<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Support;

class SessionHelper
{
    /**
     * @param  array<string, mixed>  $errors
     */
    public static function flashErrors(array $errors): void
    {
        $_SESSION['errors'] = $errors;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function flashOldInput(array $data): void
    {
        $_SESSION['old'] = $data;
    }

    public static function clearFlash(): void
    {
        unset($_SESSION['errors'], $_SESSION['old']);
    }
}
