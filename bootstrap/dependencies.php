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
use App\Modules\Core\Infrastructure\Events\Dispatcher;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Queue\QueueManager;
use App\Modules\Core\Infrastructure\Cache\CacheInterface;
use App\Modules\Core\Infrastructure\Cache\CacheManager;
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
    // JWT Service
    JwtService::class => factory(function (): JwtService {
        $secret = $_ENV['JWT_SECRET'] ?? '';

        return new JwtService($secret);
    }),
    // Cache system
    CacheManager::class => autowire(CacheManager::class),
    CacheInterface::class => factory(fn (CacheManager $manager): CacheInterface => $manager->driver()),    UpdateItemActionInterface::class => \DI\autowire(UpdateItemAction::class),];
