<?php

namespace App\Http\Controllers;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class Controller
{
    protected mixed $validator;
    protected $csrfNameKey;
    protected $csrfValueKey;
    protected mixed $session;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container)
    {
        $this->validator = $container->get('validator');
        $csrf = $container->get('csrf');
        $this->csrfNameKey = $csrf->getTokenNameKey();
        $this->csrfValueKey = $csrf->getTokenValueKey();
        $this->session = $container->get('session');
    }

    protected function getCsrf($request): array
    {
        return [
            $this->csrfNameKey => $request->getAttribute($this->csrfNameKey),
            $this->csrfValueKey => $request->getAttribute($this->csrfValueKey),
        ];
    }

}