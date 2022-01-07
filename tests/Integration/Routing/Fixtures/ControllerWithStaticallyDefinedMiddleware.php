<?php

namespace Illuminate\Tests\Integration\Routing\Fixtures;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class ControllerWithStaticallyDefinedMiddleware extends Controller
{
    public function __construct()
    {
        $_SERVER['controller_with_statically_defined_middleware_was_constructed'] = true;
    }

    public static function getMiddleware(): array
    {
        static::middleware('auth');

        static::middleware(function (Request $request): RedirectResponse {
            return new RedirectResponse('https://www.foo.com');
        });

        return parent::getMiddleware();
    }

    public function __invoke(): Response
    {
        return new Response('foobar');
    }
}
