<?php

// app/View/Blade.php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\View;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use RuntimeException;

class Blade
{
    protected Factory $factory;

    protected BladeCompiler $compiler;

    protected Filesystem $files;

    public function __construct(
        protected string $viewsPath,
        protected string $cachePath,
        protected array $sharedData = []
    ) {
        $this->files = new Filesystem();
        $this->setupBlade();
        $this->shareDefaults();
    }

    public function make(string $template, array $data = []): string
    {
        try {
            return $this->factory->make($template, array_merge($this->sharedData, $data))->render();
        } catch (Exception $e) {
            throw new RuntimeException("Failed to render view '{$template}': ".$e->getMessage(), 0, $e);
        }
    }

    public function share(string $key, $value = null): void
    {
        $this->sharedData[$key] = $value;
        $this->factory->share($key, $value);
    }

    public function exists(string $template): bool
    {
        return $this->factory->exists($template);
    }

    public function getCompiler(): BladeCompiler
    {
        return $this->compiler;
    }

    public function getFactory(): Factory
    {
        return $this->factory;
    }

    public function addExtension(string $extension, string $engine = 'blade'): void
    {
        $this->factory->addExtension($extension, $engine);
    }

    protected function shareDefaults(): void
    {
        $this->share('_session', $_SESSION ?? []);
        $this->share('errors', $_SESSION['errors'] ?? []);
        $this->share('old', $_SESSION['old'] ?? []);

        $this->share('_token', $_SESSION['csrf_token'] ?? '');

        foreach ($this->sharedData as $key => $value) {
            $this->factory->share($key, $value);
        }
    }

    private function setupBlade(): void
    {
        $config = new Repository([
            'view.paths' => [$this->viewsPath],
            'view.compiled' => $this->cachePath,
        ]);

        $this->compiler = new class($this->files, $config->get('view.compiled')) extends BladeCompiler
        {
            protected function compileCsrf(): string
            {
                return '<?php echo \'<input type="hidden" name="_token" value="\'.htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\', ENT_QUOTES).\'">\'; ?>';
            }
        };

        $resolver = new EngineResolver();
        $resolver->register('blade', fn (): CompilerEngine => new CompilerEngine($this->compiler));

        $finder = new FileViewFinder($this->files, $config->get('view.paths'));
        $this->factory = new Factory($resolver, $finder, new Dispatcher());
    }
}
