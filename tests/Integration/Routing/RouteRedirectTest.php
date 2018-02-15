<?php

namespace Illuminate\Tests\Integration\Routing;

use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class RouteRedirectTest extends TestCase
{
    public function test_route_redirect()
    {
        Route::redirect('from', 'to', 301);

        $this->get('/from')->assertRedirect('to');
    }

    public function test_route_redirect_with_params()
    {
        Route::redirect('from/{param}/{param2?}', 'to', 301);

        $this->get('/from/value1/value2')->assertRedirect('to');

        $this->get('/from/value1')->assertRedirect('to');
    }

    public function test_route_redirect_to_external_location()
    {
        Route::redirect('from', 'https://laravel.com/');

        $this->get('/from')->assertRedirect('https://laravel.com/');
    }
}
