<?php

// bootstrap/app.php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Modules\Core\Infrastructure\Http\RequestHandlers\FormRequestStrategy;
use App\Modules\Core\Infrastructure\Validation\ConfigurationException;
use App\Modules\Core\Infrastructure\Validation\EnvironmentValidator;
use DI\ContainerBuilder;
use Dotenv\Dotenv;

// Load environment variables from .env file
if (file_exists(__DIR__.'/../.env')) {
    $dotenv = Dotenv::createUnsafeImmutable(__DIR__.'/..');
    $dotenv->safeLoad();
}

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Validation\Factory;
use Slim\Factory\AppFactory;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

// ═════════════════════════════════════════════════════════════════════════════
// 1. ENVIRONMENT VALIDATION (Fail-Fast Pattern)
// ═════════════════════════════════════════════════════════════════════════════
try {
    EnvironmentValidator::validate();
} catch (ConfigurationException $configurationException) {
    $isCli = PHP_SAPI === 'cli';

    if ($isCli) {
        // CLI output - detailed error message
        echo "\n" . $configurationException->getDetailedMessage() . "\n\n";
        exit(1);
    }

    // HTTP output - JSON error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode($configurationException->getSummary(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit(1);
}

// Check for warnings (non-critical issues)
$warnings = EnvironmentValidator::getWarnings();
if ($warnings !== [] && ($_ENV['APP_ENV'] ?? 'production') === 'local') {
    error_log('Environment warnings: ' . implode(', ', $warnings));
}

// ═════════════════════════════════════════════════════════════════════════════
// 2. CONTAINER INITIALIZATION
// ═════════════════════════════════════════════════════════════════════════════
$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->addDefinitions(require __DIR__.'/../bootstrap/dependencies.php');
$container = $containerBuilder->build();

// Start native PHP session (used by CSRF, flash messages, etc.)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create Symfony Session wrapper around existing session
$storage = new NativeSessionStorage();
$session = new Session($storage);
// Don't start again - just use the already started native session
$container->set(Session::class, fn (): Session => $session);

// ═════════════════════════════════════════════════════════════════════════════
// 3. SERVICE CONFIGURATION
// ═════════════════════════════════════════════════════════════════════════════

// Configure database
$capsule = new Capsule;
require __DIR__.'/../bootstrap/database.php';

// Configure validation
$validation = require __DIR__.'/../bootstrap/validation.php';
$validation($container, $capsule);

// Configure Blade templating
(require __DIR__.'/../bootstrap/blade.php')($container);

// Configure Event system
(require __DIR__.'/../bootstrap/events.php')($container);

// Configure Eloquent features (auto eager loading, etc.)
require __DIR__.'/../bootstrap/eloquent.php';

// ═════════════════════════════════════════════════════════════════════════════
// 4. SLIM APPLICATION SETUP
// ═════════════════════════════════════════════════════════════════════════════
AppFactory::setContainer($container);
$app = AppFactory::createFromContainer($container);

// Configure request handler strategy
$strategy = new FormRequestStrategy($container, $container->get(Factory::class));
$app->getRouteCollector()->setDefaultInvocationStrategy($strategy);

// Load middleware
(require __DIR__.'/../bootstrap/middleware.php')($app, $container);

// Load routes
(require __DIR__.'/../routes/web.php')($app);
(require __DIR__.'/../routes/api.php')($app);

// Load and boot modules
(require __DIR__.'/modules.php')($app, $container);

$app->run();
