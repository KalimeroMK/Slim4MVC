<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DashboardController extends Controller
{
    /**
     * @throws Exception
     */
    public function dashboard(Request $request, Response $response): Response
    {
        return view('admin.dashboard', $response);
    }
}
