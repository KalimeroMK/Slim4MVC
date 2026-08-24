<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Modules\Core\Infrastructure\Http\Exceptions\HttpTokenMismatchException;
use App\Modules\Core\Infrastructure\Http\Middleware\CsrfMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use Tests\TestCase;

/**
 * A refused write has to say so with a status the error middleware will actually send.
 *
 * The refusal itself always worked; what did not was the status. Both branches threw a
 * plain RuntimeException carrying the intended code as its exception code, which Slim's
 * error handler does not read, so every rejected form came back 500.
 */
#[CoversClass(CsrfMiddleware::class)]
final class CsrfMiddlewareTest extends TestCase
{
    public function test_a_get_passes_straight_through(): void
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost/');

        $response = (new CsrfMiddleware())->process($request, $this->handler());

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_a_matching_token_passes(): void
    {
        (new CsrfMiddleware())->process(
            (new ServerRequestFactory())->createServerRequest('GET', 'http://localhost/'),
            $this->handler()
        );

        $response = (new CsrfMiddleware())->process(
            $this->post(['_token' => $_SESSION['csrf_token']]),
            $this->handler()
        );

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_a_missing_token_is_refused_as_419(): void
    {
        $this->expectException(HttpTokenMismatchException::class);

        try {
            (new CsrfMiddleware())->process($this->post([]), $this->handler());
        } catch (HttpTokenMismatchException $e) {
            $this->assertSame(419, $e->getCode(), 'the status the error middleware will send');
            throw $e;
        }
    }

    public function test_a_wrong_token_is_refused_as_419(): void
    {
        $this->expectException(HttpTokenMismatchException::class);

        (new CsrfMiddleware())->process($this->post(['_token' => 'not-the-token']), $this->handler());
    }

    public function test_a_body_that_is_not_a_form_is_refused_as_400(): void
    {
        $this->expectException(HttpBadRequestException::class);

        try {
            (new CsrfMiddleware())->process($this->post(null), $this->handler());
        } catch (HttpBadRequestException $e) {
            $this->assertSame(400, $e->getCode());
            throw $e;
        }
    }

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface
        {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(200);
            }
        };
    }

    private function post(mixed $body): ServerRequestInterface
    {
        return (new ServerRequestFactory())
            ->createServerRequest('POST', 'http://localhost/admin/thing')
            ->withParsedBody($body);
    }
}
