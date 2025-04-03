<?php

// app/Trait/ValidatesRequests.php

declare(strict_types=1);

namespace App\Trait;

use App\Http\Requests\FormRequest;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ValidatesRequests
{
    abstract protected function getContainer(): ContainerInterface;

    protected function validateRequest(
        ServerRequestInterface $request,
        string $requestClass
    ): ?ResponseInterface {
        /** @var FormRequest $formRequest */
        $formRequest = new $requestClass(
            $request,
            $this->getContainer()->get('validator')
        );

        return $formRequest->validate();
    }

    protected function validatedData(
        ServerRequestInterface $request,
        string $requestClass
    ): array {
        /** @var FormRequest $formRequest */
        $formRequest = new $requestClass(
            $request,
            $this->getContainer()->get('validator')
        );

        return $formRequest->validated();
    }
}
