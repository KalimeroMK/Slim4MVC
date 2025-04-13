<?php

declare(strict_types=1);

namespace App\Http\RequestHandlers;

use App\Http\Requests\FormRequest;
use App\Support\RequestResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

class FormRequestStrategy implements RequestHandlerInvocationStrategyInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RequestResolver $resolver
    ) {}

    /**
     * @throws ReflectionException
     */
    public function __invoke(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): ResponseInterface {
        $callable = $this->resolveCallable($callable);
        $parameters = $this->resolveParameters($callable, $request, $response, $routeArguments);

        return call_user_func_array($callable, $parameters);
    }

    private function resolveCallable(callable $callable): callable
    {
        if (is_array($callable) && is_string($callable[0])) {
            $callable[0] = $this->container->get($callable[0]);
        }

        return $callable;
    }

    /**
     * @throws ReflectionException
     */
    private function resolveParameters(
        callable $callable,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): array {
        $reflection = $this->createReflection($callable);
        $parameters = [];

        foreach ($reflection->getParameters() as $param) {
            $parameters[] = $this->resolveParameter($param, $request, $response, $routeArguments);
        }

        return $parameters;
    }

    private function createReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }

    private function resolveParameter(
        ReflectionParameter $parameter,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): mixed {
        $type = $parameter->getType();
        $name = $parameter->getName();

        if ($type === null) {
            return $routeArguments[$name] ?? null;
        }

        $typeName = $type->getName();

        // Handle form requests
        if (is_subclass_of($typeName, FormRequest::class)) {
            return $this->resolver->resolve($typeName, $request);
        }

        // Handle PSR-7 interfaces
        if ($typeName === ServerRequestInterface::class) {
            return $request;
        }

        if ($typeName === ResponseInterface::class) {
            return $response;
        }

        // Handle route arguments
        if (isset($routeArguments[$name])) {
            return $routeArguments[$name];
        }

        // Try to resolve from container
        if ($this->container->has($typeName)) {
            return $this->container->get($typeName);
        }

        return null;
    }
}
