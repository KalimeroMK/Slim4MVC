<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\View;

use App\Modules\Core\Infrastructure\Support\AuthHelper;
use eftec\bladeone\BladeOne;
use RuntimeException;

/**
 * Lightweight Blade engine using BladeOne.
 * Optimized for Slim 4 micro-framework.
 */
class Blade
{
    protected BladeOne $engine;

    protected string $viewsPath;

    protected string $cachePath;

    protected array $sharedData = [];

    public function __construct(
        string $viewsPath,
        string $cachePath,
        array $sharedData = []
    ) {
        $this->viewsPath = $viewsPath;
        $this->cachePath = $cachePath;
        $this->sharedData = $sharedData;
        
        $this->setupEngine();
        $this->shareDefaults();
    }

    /**
     * Render a view template.
     */
    public function make(string $template, array $data = []): string
    {
        try {
            return $this->engine->run($template, array_merge($this->sharedData, $data));
        } catch (\Exception $e) {
            throw new RuntimeException("Failed to render view '{$template}': " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Share data across all views.
     */
    public function share(string $key, $value = null): void
    {
        $this->sharedData[$key] = $value;
        $this->engine->share($key, $value);
    }

    /**
     * Check if view exists.
     */
    public function exists(string $template): bool
    {
        $path = $this->viewsPath . '/' . str_replace('.', '/', $template) . '.blade.php';
        
        return file_exists($path);
    }

    /**
     * Get the BladeOne engine instance.
     */
    public function getEngine(): BladeOne
    {
        return $this->engine;
    }

    /**
     * Add custom directive.
     */
    public function directive(string $name, callable $handler): void
    {
        $this->engine->directive($name, $handler);
    }

    /**
     * Set default shared data.
     */
    protected function shareDefaults(): void
    {
        $this->share('_session', $_SESSION ?? []);
        $this->share('errors', $_SESSION['errors'] ?? []);
        $this->share('old', $_SESSION['old'] ?? []);
        $this->share('_token', $_SESSION['csrf_token'] ?? '');
        
        // Register CSRF directive
        $this->directive('csrf', function () {
            return '<?php echo \'<input type="hidden" name="_token" value="\' . htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\', ENT_QUOTES) . \'">\'; ?>';
        });
        
        // Register method directive
        $this->directive('method', function ($method) {
            return '<?php echo \'<input type="hidden" name="_method" value="\' . ' . $method . ' . \'">\'; ?>';
        });
    }

    /**
     * Initialize BladeOne engine.
     */
    protected function setupEngine(): void
    {
        $mode = ($_ENV['APP_ENV'] ?? 'local') === 'production' 
            ? BladeOne::MODE_FAST 
            : BladeOne::MODE_AUTO;
        
        $this->engine = new BladeOne($this->viewsPath, $this->cachePath, $mode);
        
        $this->registerDirectives();
    }

    /**
     * Register custom Blade directives.
     */
    protected function registerDirectives(): void
    {
        // @csrf - CSRF token field
        $this->engine->directive('csrf', function () {
            return '<?php echo \App\Modules\Core\Infrastructure\Support\AuthHelper::csrfField(); ?>';
        });

        // @csrfToken - CSRF token value only
        $this->engine->directive('csrfToken', function () {
            return '<?php echo \App\Modules\Core\Infrastructure\Support\AuthHelper::csrfToken(); ?>';
        });

        // @method('PUT') - HTTP method spoofing
        $this->engine->directive('method', function ($method) {
            return '<?php echo \App\Modules\Core\Infrastructure\Support\AuthHelper::methodField(' . $method . '); ?>';
        });

        // @auth - Check if user is authenticated
        $this->engine->directive('auth', function () {
            return '<?php if (\App\Modules\Core\Infrastructure\Support\AuthHelper::check()): ?>';
        });

        // @endauth
        $this->engine->directive('endauth', function () {
            return '<?php endif; ?>';
        });

        // @guest - Check if user is not authenticated
        $this->engine->directive('guest', function () {
            return '<?php if (\App\Modules\Core\Infrastructure\Support\AuthHelper::guest()): ?>';
        });

        // @endguest
        $this->engine->directive('endguest', function () {
            return '<?php endif; ?>';
        });

        // @can('permission') - Check if user has permission
        $this->engine->directive('can', function ($permission) {
            return '<?php if (\App\Modules\Core\Infrastructure\Support\AuthHelper::can(' . $permission . ')): ?>';
        });

        // @endcan
        $this->engine->directive('endcan', function () {
            return '<?php endif; ?>';
        });

        // @role('admin') - Check if user has role
        $this->engine->directive('role', function ($role) {
            return '<?php if (\App\Modules\Core\Infrastructure\Support\AuthHelper::hasRole(' . $role . ')): ?>';
        });

        // @endrole
        $this->engine->directive('endrole', function () {
            return '<?php endif; ?>';
        });
    }
}
