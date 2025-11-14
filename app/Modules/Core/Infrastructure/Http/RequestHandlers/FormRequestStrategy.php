<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\RequestHandlers;

use App\Modules\Core\Infrastructure\Http\Requests\FormRequest;
use Illuminate\Validation\Factory as ValidatorFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionType;
use Slim\Interfaces\RequestHandlerInvocationStrategyInterface;

class FormRequestStrategy implements RequestHandlerInvocationStrategyInterface
{
    /**
     * Cache for reflection objects to avoid recreating them on each request.
     *
     * @var array<string, ReflectionFunctionAbstract>
     */
    private array $reflectionCache = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ValidatorFactory $validatorFactory
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
        $reflection = $this->getReflection($callable);
        $parameters = [];

        foreach ($reflection->getParameters() as $param) {
            $parameters[] = $this->resolveParameter($param, $request, $response, $routeArguments);
        }

        return $parameters;
    }

    /**
     * Get reflection object with caching for better performance.
     *
     * @throws ReflectionException
     */
    private function getReflection(callable $callable): ReflectionFunctionAbstract
    {
        $cacheKey = $this->getCacheKey($callable);

        if (! isset($this->reflectionCache[$cacheKey])) {
            $this->reflectionCache[$cacheKey] = $this->createReflection($callable);
        }

        return $this->reflectionCache[$cacheKey];
    }

    private function getCacheKey(callable $callable): string
    {
        if (is_array($callable)) {
            $class = is_object($callable[0]) ? get_class($callable[0]) : $callable[0];

            return $class.'::'.$callable[1];
        }

        if (is_string($callable)) {
            return $callable;
        }

        if (is_object($callable)) {
            return spl_object_hash($callable);
        }

        return serialize($callable);
    }

    private function createReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        return new ReflectionFunction($callable);
    }

    /**
     * Resolve a single parameter using optimized type checking order.
     */
    private function resolveParameter(
        ReflectionParameter $parameter,
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $routeArguments
    ): mixed {
        $type = $parameter->getType();
        $name = $parameter->getName();

        // Fast path: no type hint - check route arguments
        if ($type === null) {
            return $routeArguments[$name] ?? null;
        }

        // Fast path: route arguments (most common after FormRequest)
        if (isset($routeArguments[$name])) {
            return $routeArguments[$name];
        }

        $typeName = $this->getTypeName($type);

        // Fast path: PSR-7 interfaces (very common)
        if ($typeName === ServerRequestInterface::class) {
            return $request;
        }

        if ($typeName === ResponseInterface::class) {
            return $response;
        }

        // Form requests (common, but check after PSR-7)
        if ($this->isFormRequest($typeName)) {
            return $this->resolveFormRequest($typeName, $request);
        }

        // Container resolution (fallback)
        if ($this->container->has($typeName)) {
            return $this->container->get($typeName);
        }

        // If parameter has default value, return it
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        // Last resort: return null (will likely cause error, but better than crashing)
        return null;
    }

    /**
     * Get type name from ReflectionType.
     */
    private function getTypeName(ReflectionType $type): string
    {
        return $type->getName();
    }

    /**
     * Check if type is a FormRequest subclass (optimized).
     */
    private function isFormRequest(string $typeName): bool
    {
        // Fast path: exact match
        if ($typeName === FormRequest::class) {
            return true;
        }

        // Check if it's a subclass
        return is_subclass_of($typeName, FormRequest::class);
    }

    /**
     * Resolve FormRequest instance (integrated from RequestResolver for better performance).
     */
    private function resolveFormRequest(string $requestClass, ServerRequestInterface $request): FormRequest
    {
        return new $requestClass($request, $this->validatorFactory);
    }
}
