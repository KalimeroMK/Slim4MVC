<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController
{
    /**
     * @throws Exception
     */
    public function index(Request $request, Response $response): Response
    {
        return view($response, 'welcome');
    }
}
