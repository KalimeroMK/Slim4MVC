<?php

declare(strict_types=1);

// config/dependencies.php
use App\Modules\Auth\Application\Actions\Auth\LoginAction;
use App\Modules\Auth\Application\Actions\Auth\PasswordRecoveryAction;
use App\Modules\Auth\Application\Actions\Auth\RegisterAction;
use App\Modules\Auth\Application\Actions\Auth\ResetPasswordAction;
use App\Modules\Auth\Application\Actions\Auth\WebLoginAction;
use App\Modules\Auth\Application\Interfaces\Auth\LoginActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\PasswordRecoveryActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\RegisterActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\ResetPasswordActionInterface;
use App\Modules\Auth\Application\Interfaces\Auth\WebLoginActionInterface;
use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Cache\CacheManager;
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Queue\QueueManager;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtService;
use App\Modules\Core\Infrastructure\Support\AdvancedJwtServiceInterface;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\Permission\Application\Actions\CreatePermissionAction;
use App\Modules\Permission\Application\Actions\UpdatePermissionAction;
use App\Modules\Permission\Application\Interfaces\CreatePermissionActionInterface;
use App\Modules\Permission\Application\Interfaces\UpdatePermissionActionInterface;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;
use App\Modules\Role\Application\Actions\CreateRoleAction;
use App\Modules\Role\Application\Actions\UpdateRoleAction;
use App\Modules\Role\Application\Interfaces\CreateRoleActionInterface;
use App\Modules\Role\Application\Interfaces\UpdateRoleActionInterface;
use App\Modules\Role\Infrastructure\Repositories\RoleRepository;
use App\Modules\User\Application\Actions\CreateUserAction;
use App\Modules\User\Application\Actions\UpdateUserAction;
use App\Modules\User\Application\Interfaces\CreateUserActionInterface;
use App\Modules\User\Application\Interfaces\UpdateUserActionInterface;
use App\Modules\User\Infrastructure\Repositories\UserRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Response;

use function DI\autowire;
use function DI\factory;

return [
    // PSR-7 Response factory
    ResponseInterface::class => factory(fn (): Response => new Response()),
    // PSR-17 Response Factory
    ResponseFactoryInterface::class => factory(fn (): ResponseFactoryInterface => new ResponseFactory()),
    RegisterActionInterface::class => autowire(RegisterAction::class),
    LoginActionInterface::class => autowire(LoginAction::class),
    PasswordRecoveryActionInterface::class => autowire(PasswordRecoveryAction::class),
    ResetPasswordActionInterface::class => autowire(ResetPasswordAction::class),
    WebLoginActionInterface::class => autowire(WebLoginAction::class),
    CreateRoleActionInterface::class => autowire(CreateRoleAction::class),
    UpdateRoleActionInterface::class => autowire(UpdateRoleAction::class),
    CreatePermissionActionInterface::class => autowire(CreatePermissionAction::class),
    UpdatePermissionActionInterface::class => autowire(UpdatePermissionAction::class),
    CreateUserActionInterface::class => autowire(CreateUserAction::class),
    UpdateUserActionInterface::class => autowire(UpdateUserAction::class),
    // Repositories
    UserRepository::class => autowire(UserRepository::class),
    RoleRepository::class => autowire(RoleRepository::class),
    PermissionRepository::class => autowire(PermissionRepository::class),
    // Event system
    Dispatcher::class => autowire(Dispatcher::class),
    // Queue system
    QueueManager::class => autowire(QueueManager::class),
    Queue::class => factory(fn (QueueManager $queueManager): Queue => $queueManager->queue()),
    // JWT Service (simple access-token encoding/decoding)
    JwtService::class => factory(function (): JwtService {
        $secret = $_ENV['JWT_SECRET'] ?? '';

        return new JwtService($secret);
    }),

    // Advanced JWT Service (access + refresh token pairs, rotation, revocation)
    AdvancedJwtService::class => factory(function (): AdvancedJwtService {
        $secret    = $_ENV['JWT_SECRET'] ?? '';
        $algorithm = $_ENV['JWT_ALGORITHM'] ?? 'HS256';
        $issuer    = $_ENV['JWT_ISSUER'] ?? null;
        $audience  = $_ENV['JWT_AUDIENCE'] ?? null;

        // Redis client is optional — refresh token rotation requires it
        $redisClient = null;
        if (($_ENV['REDIS_HOST'] ?? '') !== '') {
            try {
                $redisClient = new \Predis\Client([
                    'host'     => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port'     => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                    'password' => $_ENV['REDIS_PASSWORD'] ?? null,
                    'database' => (int) ($_ENV['REDIS_DATABASE'] ?? 0),
                ]);
            } catch (\Throwable) {
                // Redis unavailable — run without token rotation
            }
        }

        return new AdvancedJwtService(
            secret: $secret,
            algorithm: $algorithm,
            issuer: $issuer !== '' ? $issuer : null,
            audience: $audience !== '' ? $audience : null,
            client: $redisClient,
        );
    }),
    // Bind interface to the concrete factory so autowired actions get the same instance
    AdvancedJwtServiceInterface::class => factory(fn (AdvancedJwtService $svc): AdvancedJwtServiceInterface => $svc),
    // Cache system
    CacheManager::class => autowire(CacheManager::class),
    CacheInterface::class => factory(fn (CacheManager $cacheManager): CacheInterface => $cacheManager->driver()),    UpdateItemActionInterface::class => \DI\autowire(UpdateItemAction::class),];
