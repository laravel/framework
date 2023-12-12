<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

include_once 'Enums.php';

class ImplicitBackedEnumRouteBindingTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set(['app.key' => 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF']);
    }

    public function testWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\CategoryBackedEnum;

Route::get('/categories/{category}', function (CategoryBackedEnum \$category) {
    return \$category->value;
})->middleware('web');

Route::get('/categories-default/{category?}', function (CategoryBackedEnum \$category = CategoryBackedEnum::Fruits) {
    return \$category->value;
})->middleware('web');
PHP);

        $response = $this->get('/categories/fruits');
        $response->assertSee('fruits');

        $response = $this->get('/categories/people');
        $response->assertSee('people');

        $response = $this->get('/categories/cars');
        $response->assertNotFound(404);

        $response = $this->get('/categories-default/');
        $response->assertSee('fruits');

        $response = $this->get('/categories-default/people');
        $response->assertSee('people');

        $response = $this->get('/categories-default/fruits');
        $response->assertSee('fruits');
    }

    public function testWithoutRouteCachingEnabled()
    {
        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/categories/{category}', function (CategoryBackedEnum $category) {
            return $category->value;
        })->middleware(['web']);

        Route::post('/categories-default/{category?}', function (CategoryBackedEnum $category = CategoryBackedEnum::Fruits) {
            return $category->value;
        })->middleware('web');

        $response = $this->post('/categories/fruits');
        $response->assertSee('fruits');

        $response = $this->post('/categories/people');
        $response->assertSee('people');

        $response = $this->post('/categories/cars');
        $response->assertNotFound(404);

        $response = $this->post('/categories-default/');
        $response->assertSee('fruits');

        $response = $this->post('/categories-default/people');
        $response->assertSee('people');

        $response = $this->post('/categories-default/fruits');
        $response->assertSee('fruits');
    }
}
