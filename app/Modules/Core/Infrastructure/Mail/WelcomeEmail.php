<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Mail;

use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use App\Modules\User\Infrastructure\Models\User;

/**
 * Welcome email mailable.
 */
class WelcomeEmail extends Mailable
{
    protected User $user;

    public function __construct(
        Mailer $mailer,
        Blade $blade,
        User $user
    ) {
        parent::__construct($mailer, $blade);
        $this->user = $user;
        $this->to($user->email);
    }

    protected function template(): string
    {
        return 'email.welcome';
    }

    protected function getSubject(): string
    {
        return 'Welcome to our platform!';
    }

    protected function buildData(): array
    {
        return [
            'user' => $this->user,
        ];
    }
}
