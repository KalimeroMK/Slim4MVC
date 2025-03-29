<?php

namespace Support;

use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\View\FileViewFinder;

class BladeFactory
{
    protected $factory;

    public function __construct()
    {
        $this->setupBlade();
    }

    private function setupBlade(): void
    {
        $files = new Filesystem();
        $config = new Repository([
            'view.paths' => [__DIR__ . '/../resources/views'],
            'view.compiled' => __DIR__ . '/../storage/cache',
        ]);

        $compiler = new BladeCompiler($files, $config->get('view.compiled'));

        $resolver = new EngineResolver();
        $resolver->register('blade', function () use ($compiler) {
            return new CompilerEngine($compiler);
        });

        $finder = new FileViewFinder($files, $config->get('view.paths'));
        $this->factory = new Factory($resolver, $finder, new Dispatcher);
    }

    public function render(string $view, array $data = []): string
    {
        return $this->factory->make($view, $data)->render();
    }
}
