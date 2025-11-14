<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Middleware;

use App\Modules\Core\Infrastructure\Exceptions\BadRequestException;
use App\Modules\Core\Infrastructure\Exceptions\ForbiddenException;
use App\Modules\Core\Infrastructure\Exceptions\InvalidCredentialsException;
use App\Modules\Core\Infrastructure\Exceptions\NotFoundException;
use App\Modules\Core\Infrastructure\Exceptions\UnauthorizedException;
use App\Modules\Core\Infrastructure\Exceptions\ValidationException;
use App\Modules\Core\Infrastructure\Support\ApiResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use RuntimeException;
use Throwable;

/**
 * Middleware to handle exceptions and convert them to appropriate API responses.
 */
class ExceptionHandlerMiddleware implements MiddlewareInterface
{
    /**
     * Process the request and handle exceptions.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (NotFoundException $e) {
            return ApiResponse::notFound($e->getMessage());
        } catch (UnauthorizedException|InvalidCredentialsException $e) {
            return ApiResponse::unauthorized($e->getMessage());
        } catch (ForbiddenException $e) {
            return ApiResponse::forbidden($e->getMessage());
        } catch (BadRequestException $e) {
            return ApiResponse::badRequest($e->getMessage());
        } catch (ValidationException $e) {
            return ApiResponse::validationError($e->getErrors(), $e->getMessage());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return ApiResponse::notFound('Resource not found');
        } catch (RuntimeException $e) {
            // Log the exception for debugging
            error_log('RuntimeException: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            return ApiResponse::error($e->getMessage(), 500);
        } catch (Throwable $e) {
            // Log unexpected exceptions
            error_log('Unexpected exception: '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());

            return ApiResponse::error('An unexpected error occurred', 500);
        }
    }
}
