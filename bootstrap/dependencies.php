<?php

declare(strict_types=1);

// config/dependencies.php
use App\Actions\Permission\CreatePermissionAction;
use App\Actions\Permission\UpdatePermissionAction;
use App\Interface\Permission\CreatePermissionActionInterface;
use App\Interface\Permission\UpdatePermissionActionInterface;
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
use App\Modules\Core\Infrastructure\Queue\FileQueue;
use App\Modules\Core\Infrastructure\Queue\Queue;
use App\Modules\Core\Infrastructure\Queue\QueueManager;
use App\Modules\Core\Infrastructure\Support\JwtService;
use App\Modules\Permission\Infrastructure\Repositories\PermissionRepository;
use App\Modules\Product\Application\Actions\CreateItemAction;
use App\Modules\Product\Application\Actions\UpdateItemAction;
use App\Modules\Product\Application\Interfaces\CreateItemActionInterface;
use App\Modules\Product\Application\Interfaces\UpdateItemActionInterface;
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

use function DI\autowire;
use function DI\factory;

return [
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
    Queue::class => factory(function (QueueManager $queueManager): Queue {
        return $queueManager->queue();
    }),
    // JWT Service
    JwtService::class => factory(function (): JwtService {
        $secret = $_ENV['JWT_SECRET'] ?? '';

        return new JwtService($secret);
    }),
    CreateItemActionInterface::class => \DI\autowire(CreateItemAction::class),
    UpdateItemActionInterface::class => \DI\autowire(UpdateItemAction::class),
];
