<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\View;

use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class BladeFactory
{
    protected $factory;

    public function __construct()
    {
        $this->setupBlade();
    }

    public function render(string $view, array $data = []): string
    {
        return $this->factory->make($view, $data)->render();
    }

    private function setupBlade(): void
    {
        $filesystem = new Filesystem();
        $repository = new Repository([
            'view.paths' => [__DIR__.'/../../resources/views'],
            'view.compiled' => __DIR__.'/../../storage/cache/view',
        ]);

        $bladeCompiler = new BladeCompiler($filesystem, $repository->get('view.compiled'));

        $engineResolver = new EngineResolver();
        $engineResolver->register('blade', fn (): CompilerEngine => new CompilerEngine($bladeCompiler));

        $fileViewFinder = new FileViewFinder($filesystem, $repository->get('view.paths'));
        $this->factory = new Factory($engineResolver, $fileViewFinder, new Dispatcher);
    }
}
