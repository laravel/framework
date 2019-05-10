<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Http\Response;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;

/**
 * @group integration
 */
class RouteRedirectTest extends TestCase
{
    /**
     * @dataProvider  routeRedirectDataSets
     *
     * @param  string  $redirectFrom
     * @param  string  $redirectTo
     * @param  string  $responseUri
     */
    public function testRouteRedirect($redirectFrom, $redirectTo, $responseUri)
    {
        Route::redirect($redirectFrom, $redirectTo, Response::HTTP_MOVED_PERMANENTLY);

        $response = $this->get($responseUri);
        $response->assertRedirect($redirectTo);
        $response->assertStatus(Response::HTTP_MOVED_PERMANENTLY);
    }

    public function routeRedirectDataSets(): array
    {
        return [
            'route redirect with no parameters' => ['from', 'to', '/from'],
            'route redirect with one parameter' => ['from/{param}/{param2?}', 'to', '/from/value1'],
            'route redirect with two parameters' => ['from/{param}/{param2?}', 'to', '/from/value1/value2'],
        ];
    }
}
