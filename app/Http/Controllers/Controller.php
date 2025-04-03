<?php

// app/Http/Controllers/Controller.php

declare(strict_types=1);

namespace App\Http\Controllers;

use Psr\Container\ContainerInterface;

abstract class Controller
{
    public function __construct(
        protected ContainerInterface $container
    ) {}

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
