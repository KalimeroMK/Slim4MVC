<?php

// bootstrap/blade.php

declare(strict_types=1);

use App\View\Blade;
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

            return new class($blade)
            {
                private Blade $blade;

                public function __construct(Blade $blade)
                {
                    $this->blade = $blade;
                }

                public function render(string $template, array $data = []): string
                {
                    try {
                        return $this->blade->make($template, $data);
                    } catch (Exception $e) {
                        throw new RuntimeException(
                            "Failed to render view '{$template}': ".$e->getMessage(),
                            0,
                            $e
                        );
                    }
                }

                public function exists(string $template): bool
                {
                    return $this->blade->exists($template);
                }
            };
        });

    } catch (Exception $e) {
        throw new RuntimeException(
            'Failed to initialize Blade templating: '.$e->getMessage(),
            0,
            $e
        );
    }
};
