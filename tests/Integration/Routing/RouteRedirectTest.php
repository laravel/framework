<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

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
     * @param  string  $requestUri
     * @param  string  $redirectUri
     */
    public function testRouteRedirect($redirectFrom, $redirectTo, $requestUri, $redirectUri)
    {
        $this->withoutExceptionHandling();
        Route::redirect($redirectFrom, $redirectTo, 301);

        $response = $this->get($requestUri);
        $response->assertRedirect($redirectUri);
        $response->assertStatus(301);
    }

    /**
     * @return \Generator
     */
    public function routeRedirectDataSets()
    {
        yield 'route redirect with no parameters' => ['from', 'to', '/from', '/to'];
        yield 'route redirect with one parameter' => ['from/{param}/{param2?}', 'to', '/from/value1', '/to'];
        yield 'route redirect with two parameters' => ['from/{param}/{param2?}', 'to', '/from/value1/value2', '/to'];
        yield 'route redirect with one parameter replacement' => ['users/{user}/repos', 'members/{user}/repos', '/users/22/repos', '/members/22/repos'];
        yield 'route redirect with two parameter replacements' => ['users/{user}/repos/{repo}', 'members/{user}/projects/{repo}', '/users/22/repos/laravel-framework', '/members/22/projects/laravel-framework'];
        yield 'route redirect with non existent optional parameter replacements' => ['users/{user?}', 'members/{user?}', '/users', '/members'];
        yield 'route redirect with existing parameter replacements' => ['users/{user?}', 'members/{user?}', '/users/22', '/members/22'];
        yield 'route redirect with two optional replacements' => ['users/{user?}/{repo?}', 'members/{user?}', '/users/22', '/members/22'];
        yield 'route redirect with two optional replacements that switch position' => ['users/{user?}/{switch?}', 'members/{switch?}/{user?}', '/users/11/22', '/members/22/11'];
    }
}
