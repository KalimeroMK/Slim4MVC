<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Jobs;

use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\Support\Paths;
use App\Modules\Core\Infrastructure\View\Blade;
use Psr\Container\ContainerInterface;

class SendEmailJob extends BaseJob
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        private readonly string $to,
        private readonly string $subject,
        private readonly string $template,
        private readonly array $data = []
    ) {}

    public function handle(?ContainerInterface $container = null): void
    {
        // Create Blade instance if container not provided
        if (! $container instanceof ContainerInterface) {
            $blade = new Blade(Paths::resources('views'), Paths::storage('cache/view'));
        } else {
            $blade = $container->get(Blade::class);
        }

        $mailer = new Mailer($blade);
        $mailer->send($this->to, $this->subject, $this->template, $this->data);
    }
}
