<?php

declare(strict_types=1);

// config/dependencies.php
use App\Actions\Auth\LoginAction;
use App\Actions\Auth\PasswordRecoveryAction;
use App\Actions\Auth\RegisterAction;
use App\Actions\Auth\ResetPasswordAction;
use App\Actions\Auth\WebLoginAction;
use App\Interface\Auth\LoginActionInterface;
use App\Interface\Auth\PasswordRecoveryActionInterface;
use App\Interface\Auth\RegisterActionInterface;
use App\Interface\Auth\ResetPasswordActionInterface;
use App\Interface\Auth\WebLoginActionInterface;

return [
    // Bind interfaces to implementations
    RegisterActionInterface::class => \DI\autowire(RegisterAction::class),
    LoginActionInterface::class => \DI\autowire(LoginAction::class),
    PasswordRecoveryActionInterface::class => \DI\autowire(PasswordRecoveryAction::class),
    ResetPasswordActionInterface::class => \DI\autowire(ResetPasswordAction::class),
    WebLoginActionInterface::class => \DI\autowire(WebLoginAction::class),

];
