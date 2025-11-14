<?php

declare(strict_types=1);

namespace App\Modules\Core\Infrastructure\Http\Controllers\Web;

use App\Modules\Core\Infrastructure\Http\Controllers\Controller;
use Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    /**
     * Display the home page.
     *
     * @param Request $request
     * @param Response $response
     * @return Response
     * @throws Exception
     */
    public function index(Request $request, Response $response): Response
    {
        return view('home', $response);
    }
}

