<?php

// bootstrap/blade.php

declare(strict_types=1);

use App\Modules\Core\Infrastructure\View\Blade;
use DI\Container;
use Psr\Container\ContainerInterface;

return function (Container $container): void {
    // Configure paths
    $viewsPath = __DIR__.'/../resources/views';
    $cachePath = __DIR__.'/../storage/cache/view';

    // Ensure cache directory exists
    if (! is_dir($cachePath)) {
        mkdir($cachePath, 0755, true);
    }

    try {
        // Initialize Blade with custom CSRF handling
        $blade = new Blade($viewsPath, $cachePath);

        // Share common variables with all views
        $blade->share('_session', $_SESSION ?? []);

        $container->set(Blade::class, $blade);

        $container->set('view', function (ContainerInterface $container): object {
            $blade = $container->get(Blade::class);

            return new readonly class($blade)
            {
                public function __construct(private Blade $blade) {}

                /**
                 * @param  array<string, mixed>  $data
                 */
                public function render(string $template, array $data = []): string
                {
                    try {
                        return $this->blade->make($template, $data);
                    } catch (Exception $exception) {
                        throw new RuntimeException(
                            sprintf("Failed to render view '%s': ", $template).$exception->getMessage(),
                            0,
                            $exception
                        );
                    }
                }

                public function exists(string $template): bool
                {
                    return $this->blade->exists($template);
                }
            };
        });

    } catch (Exception $exception) {
        throw new RuntimeException(
            'Failed to initialize Blade templating: '.$exception->getMessage(),
            0,
            $exception
        );
    }
};
