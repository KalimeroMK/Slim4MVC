<?php

declare(strict_types=1);

namespace App\Traits;

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
                // For API requests, return JSON response with validation errors
                $response = new Response();
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'errors' => $formRequest->validator()->errors()->messages(),
                    'message' => 'Validation failed',
                ]));

                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(422);
            }

            // For web requests, flash errors and redirect back
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

    /**
     * Create a JSON error response for API
     */
    protected function jsonError(
        string $message,
        array $errors = [],
        int $status = 400
    ): ResponseInterface {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}
