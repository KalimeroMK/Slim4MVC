<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Jobs;

use App\Modules\Core\Infrastructure\Support\Mailer;
use App\Modules\Core\Infrastructure\View\Blade;
use Psr\Container\ContainerInterface;

class SendEmailJob extends BaseJob
{
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
            $viewsPath = dirname(__DIR__, 3).'/resources/views';
            $cachePath = dirname(__DIR__, 3).'/storage/cache/view';
            $blade = new Blade($viewsPath, $cachePath);
        } else {
            $blade = $container->get(Blade::class);
        }

        $mailer = new Mailer($blade);
        $mailer->send($this->to, $this->subject, $this->template, $this->data);
    }
}
