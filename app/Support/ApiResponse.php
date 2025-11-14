<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ApiResponseStatus;
use App\Enums\HttpStatusCode;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Response as Psr7Response;

class ApiResponse
{
    // Response status constants (for backward compatibility)
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    // Common error messages
    public const ERROR_UNAUTHORIZED = 'Unauthorized';
    public const ERROR_FORBIDDEN = 'Forbidden';
    public const ERROR_NOT_FOUND = 'Not Found';
    public const ERROR_VALIDATION = 'Validation Error';
    public const ERROR_BAD_REQUEST = 'Bad Request';
    public const ERROR_SERVER_ERROR = 'Internal Server Error';

    /**
     * Return a successful JSON response.
     *
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     * @param string|null $message Optional message
     */
    public static function success(
        mixed $data = null,
        int|HttpStatusCode $statusCode = HttpStatusCode::OK,
        ?string $message = null
    ): Response {
        $response = new Psr7Response();
        
        $statusCodeValue = $statusCode instanceof HttpStatusCode ? $statusCode->getValue() : $statusCode;
        
        $body = [
            'status' => ApiResponseStatus::SUCCESS->getValue(),
        ];

        if ($message !== null) {
            $body['message'] = $message;
        }

        if ($data !== null) {
            $body['data'] = $data;
        }

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCodeValue);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Optional validation errors
     * @param string|null $code Optional error code
     */
    public static function error(
        string $message,
        int|HttpStatusCode $statusCode = HttpStatusCode::BAD_REQUEST,
        array $errors = [],
        ?string $code = null
    ): Response {
        $response = new Psr7Response();
        
        $statusCodeValue = $statusCode instanceof HttpStatusCode ? $statusCode->getValue() : $statusCode;
        
        $body = [
            'status' => ApiResponseStatus::ERROR->getValue(),
            'message' => $message,
        ];

        if ($code !== null) {
            $body['code'] = $code;
        }

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        $response->getBody()->write(json_encode($body, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCodeValue);
    }

    /**
     * Return a paginated JSON response.
     *
     * @param array $data Items data
     * @param int $total Total items count
     * @param int $page Current page
     * @param int $perPage Items per page
     * @param int|HttpStatusCode $statusCode HTTP status code
     */
    public static function paginated(
        array $data,
        int $total,
        int $page,
        int $perPage,
        int|HttpStatusCode $statusCode = HttpStatusCode::OK
    ): Response {
        $totalPages = (int) ceil($total / $perPage);

        return self::success([
            'items' => $data,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
            ],
        ], $statusCode);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    public static function unauthorized(?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_UNAUTHORIZED,
            HttpStatusCode::UNAUTHORIZED,
            [],
            'UNAUTHORIZED'
        );
    }

    /**
     * Return a 403 Forbidden response.
     */
    public static function forbidden(?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_FORBIDDEN,
            HttpStatusCode::FORBIDDEN,
            [],
            'FORBIDDEN'
        );
    }

    /**
     * Return a 404 Not Found response.
     */
    public static function notFound(?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_NOT_FOUND,
            HttpStatusCode::NOT_FOUND,
            [],
            'NOT_FOUND'
        );
    }

    /**
     * Return a 422 Validation Error response.
     */
    public static function validationError(array $errors, ?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_VALIDATION,
            HttpStatusCode::UNPROCESSABLE_ENTITY,
            $errors,
            'VALIDATION_ERROR'
        );
    }

    /**
     * Return a 400 Bad Request response.
     */
    public static function badRequest(?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_BAD_REQUEST,
            HttpStatusCode::BAD_REQUEST,
            [],
            'BAD_REQUEST'
        );
    }

    /**
     * Return a 500 Internal Server Error response.
     */
    public static function serverError(?string $message = null): Response
    {
        return self::error(
            $message ?? self::ERROR_SERVER_ERROR,
            HttpStatusCode::INTERNAL_SERVER_ERROR,
            [],
            'SERVER_ERROR'
        );
    }
}

