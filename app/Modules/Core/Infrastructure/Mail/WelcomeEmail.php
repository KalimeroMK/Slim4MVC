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
    public function __construct(
        Mailer $mailer,
        Blade $blade,
        protected User $user
    ) {
        parent::__construct($mailer, $blade);
        $email = $this->user->email;
        if ($email !== null) {
            $this->to($email);
        }
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
