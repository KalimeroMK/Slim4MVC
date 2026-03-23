<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Mail;

use App\Modules\Core\Infrastructure\Jobs\SendEmailJob;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use RuntimeException;

/**
 * Base Mailable class for email templates.
 */
abstract class Mailable
{
    protected string $to = '';

    protected string $subject = '';

    /** @var array<string, mixed> */
    protected array $data = [];

    public function __construct(
        protected Mailer $mailer,
        protected Blade $blade
    ) {}

    /**
     * Get the email template name.
     */
    abstract protected function template(): string;

    /**
     * Get the email subject.
     */
    abstract protected function getSubject(): string;

    /**
     * Build the email data.
     *
     * @return array<string, mixed>
     */
    abstract protected function buildData(): array;

    /**
     * Set recipient email address.
     */
    final public function to(string $email): self
    {
        $this->to = $email;

        return $this;
    }

    /**
     * Set email subject.
     */
    final public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Set email data.
     *
     * @param  array<string, mixed>  $data
     */
    final public function with(array $data): self
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    /**
     * Send the email.
     */
    final public function send(): bool
    {
        if ($this->to === '' || $this->to === '0') {
            throw new RuntimeException('Recipient email address is required');
        }

        $subject = $this->subject ?: $this->getSubject();
        $data = array_merge($this->buildData(), $this->data);

        return $this->mailer->send(
            $this->to,
            $subject,
            $this->template(),
            $data
        );
    }

    /**
     * Queue the email for sending.
     */
    final public function queue(?Queue $queue = null): void
    {
        $subject = $this->subject ?: $this->getSubject();
        $data = array_merge($this->buildData(), $this->data);

        $sendEmailJob = new SendEmailJob(
            $this->to,
            $subject,
            $this->template(),
            $data
        );

        if (! $queue instanceof Queue) {
            throw new RuntimeException('Queue service not available. Please provide Queue instance.');
        }

        $queue->push($sendEmailJob);
    }

    /**
     * Preview the email (for testing).
     */
    final public function preview(): string
    {
        $data = array_merge($this->buildData(), $this->data);

        return $this->blade->make($this->template(), $data);
    }
}
