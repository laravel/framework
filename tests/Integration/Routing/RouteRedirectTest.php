<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class RouteRedirectTest extends TestCase
{
    #[DataProvider('routeRedirectDataSets')]
    public function testRouteRedirect($redirectFrom, $redirectTo, $requestUri, $redirectUri)
    {
        $this->withoutExceptionHandling();
        Route::redirect($redirectFrom, $redirectTo, 301);

        $response = $this->get($requestUri);
        $response->assertRedirect($redirectUri);
        $response->assertStatus(301);
    }

    public static function routeRedirectDataSets()
    {
        return [
            'route redirect with no parameters' => ['from', 'to', '/from', '/to'],
            'route redirect with one parameter' => ['from/{param}/{param2?}', 'to', '/from/value1', '/to'],
            'route redirect with two parameters' => ['from/{param}/{param2?}', 'to', '/from/value1/value2', '/to'],
            'route redirect with one parameter replacement' => ['users/{user}/repos', 'members/{user}/repos', '/users/22/repos', '/members/22/repos'],
            'route redirect with two parameter replacements' => ['users/{user}/repos/{repo}', 'members/{user}/projects/{repo}', '/users/22/repos/laravel-framework', '/members/22/projects/laravel-framework'],
            'route redirect with non existent optional parameter replacements' => ['users/{user?}', 'members/{user?}', '/users', '/members'],
            'route redirect with existing parameter replacements' => ['users/{user?}', 'members/{user?}', '/users/22', '/members/22'],
            'route redirect with two optional replacements' => ['users/{user?}/{repo?}', 'members/{user?}', '/users/22', '/members/22'],
            'route redirect with two optional replacements that switch position' => ['users/{user?}/{switch?}', 'members/{switch?}/{user?}', '/users/11/22', '/members/22/11'],
        ];
    }

    public function testRouteRedirectWithExplicitRouteModelBinding()
    {
        $this->withoutExceptionHandling();
        Route::middleware([SubstituteBindings::class])->group(function () {
            Route::redirect('users/{user}', 'users/{user}/overview');
        });
        Route::bind('user', fn ($id) => (new User())->setAttribute('id', '999'));

        $response = $this->get('users/1');

        $response->assertRedirect('users/999/overview');
    }

    public function testToRouteHelper()
    {
        Route::get('to', function () {
            // ..
        })->name('to');

        Route::get('from-301', function () {
            return to_route('to', [], 301);
        });

        Route::get('from-302', function () {
            return to_route('to');
        });

        $this->get('from-301')
            ->assertRedirect('to')
            ->assertStatus(301)
            ->assertSee('Redirecting to');

        $this->get('from-302')
            ->assertRedirect('to')
            ->assertStatus(302)
            ->assertSee('Redirecting to');
    }
}
