<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\View;

use eftec\bladeone\BladeOne;
use Exception;
use RuntimeException;

/**
 * Lightweight Blade engine using BladeOne.
 * Optimized for Slim 4 micro-framework.
 */
class Blade
{
    protected BladeOne $engine;

    /**
     * @param  array<string, mixed>  $sharedData
     */
    public function __construct(
        protected string $viewsPath,
        protected string $cachePath,
        protected array $sharedData = []
    ) {
        $this->setupEngine();
        $this->shareDefaults();
    }

    /**
     * Render a view template.
     *
     * @param  array<string, mixed>  $data
     */
    public function make(string $template, array $data = []): string
    {
        try {
            return $this->engine->run($template, array_merge($this->sharedData, $data));
        } catch (Exception $exception) {
            throw new RuntimeException(sprintf("Failed to render view '%s': ", $template).$exception->getMessage(), 0, $exception);
        }
    }

    /**
     * Share data across all views.
     */
    public function share(string $key, mixed $value = null): void
    {
        $this->sharedData[$key] = $value;
        $this->engine->share($key, $value);
    }

    /**
     * Check if view exists.
     */
    public function exists(string $template): bool
    {
        $path = $this->viewsPath.'/'.str_replace('.', '/', $template).'.blade.php';

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
        $this->directive('csrf', fn () => '<?php echo \'<input type="hidden" name="_token" value="\' . htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\', ENT_QUOTES) . \'">\'; ?>');

        // Register method directive
        $this->directive('method', fn ($method) => '<?php echo \'<input type="hidden" name="_method" value="\' . '.$method.' . \'">\'; ?>');
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
        $this->engine->directive('csrf', fn () => '<?php echo '.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::csrfField(); ?>');

        // @csrfToken - CSRF token value only
        $this->engine->directive('csrfToken', fn () => '<?php echo '.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::csrfToken(); ?>');

        // @method('PUT') - HTTP method spoofing
        $this->engine->directive('method', fn ($method) => '<?php echo '.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::methodField('.$method.'); ?>');

        // @auth - Check if user is authenticated
        $this->engine->directive('auth', fn () => '<?php if ('.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::check()): ?>');

        // @endauth
        $this->engine->directive('endauth', fn () => '<?php endif; ?>');

        // @guest - Check if user is not authenticated
        $this->engine->directive('guest', fn () => '<?php if ('.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::guest()): ?>');

        // @endguest
        $this->engine->directive('endguest', fn () => '<?php endif; ?>');

        // @can('permission') - Check if user has permission
        $this->engine->directive('can', fn ($permission) => '<?php if ('.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::can('.$permission.')): ?>');

        // @endcan
        $this->engine->directive('endcan', fn () => '<?php endif; ?>');

        // @role('admin') - Check if user has role
        $this->engine->directive('role', fn ($role) => '<?php if ('.\App\Modules\Core\Infrastructure\Support\AuthHelper::class.'::hasRole('.$role.')): ?>');

        // @endrole
        $this->engine->directive('endrole', fn () => '<?php endif; ?>');
    }
}
