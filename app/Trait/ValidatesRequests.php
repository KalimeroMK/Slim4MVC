<?php

// app/Trait/ValidatesRequests.php

declare(strict_types=1);

namespace App\Trait;

use App\Http\Requests\FormRequest;
use App\Support\SessionHelper;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;

trait ValidatesRequests
{
    abstract protected function getContainer(): ContainerInterface;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function validateRequest(
        ServerRequestInterface $request,
        string $requestClass,
        bool $isApi = false
    ): ?ResponseInterface {
        /** @var FormRequest $formRequest */
        $formRequest = new $requestClass(
            $request,
            $this->getContainer()->get('validator')
        );

        if ($validationResponse = $formRequest->validate()) {
            if ($isApi) {
                return $validationResponse;
            }

            SessionHelper::flashErrors($formRequest->validator()->errors()->all());
            SessionHelper::flashOldInput($request->getParsedBody() ?? []);

            return $this->redirectBack();
        }

        return null;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
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

    protected function redirectBack(): ResponseInterface
    {
        $response = new Response();

        return $response
            ->withHeader('Location', $_SERVER['HTTP_REFERER'] ?? '/')
            ->withStatus(302);
    }

    protected function redirect(string $url): ResponseInterface
    {
        $response = new Response();

        return $response
            ->withHeader('Location', $url)
            ->withStatus(302);
    }
}
