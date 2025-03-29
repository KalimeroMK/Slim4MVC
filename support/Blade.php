<?php

declare(strict_types=1);

namespace Support;

use Illuminate\Config\Repository;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;

class Blade
{
    protected $factory;

    public function __construct(string $views, string $cache)
    {
        $this->setupBlade($views, $cache);
    }

    public function make(string $template, array $data = []): string
    {
        return $this->factory->make($template, $data)->render();
    }

    private function setupBlade(string $views, string $cache): void
    {
        $files = new Filesystem();
        $config = new Repository([
            'view.paths' => [$views],
            'view.compiled' => $cache,
        ]);

        $compiler = new BladeCompiler($files, $config->get('view.compiled'));

        $resolver = new EngineResolver();
        $resolver->register('blade', function () use ($compiler): CompilerEngine {
            return new CompilerEngine($compiler);
        });

        $finder = new FileViewFinder($files, $config->get('view.paths'));
        $this->factory = new Factory($resolver, $finder, new Dispatcher);
    }
}
