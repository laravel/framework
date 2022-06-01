<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Support\Facades\Route;
use Orchestra\Testbench\TestCase;

if (PHP_VERSION_ID >= 80100) {
    include 'Enums.php';
}

/**
 * @requires PHP >= 8.1
 */
class ImplicitBackedEnumRouteBindingTest extends TestCase
{
    public function testWithRouteCachingEnabled()
    {
        $this->defineCacheRoutes(<<<PHP
<?php

use Illuminate\Tests\Integration\Routing\AnimalBackedEnum;
use Illuminate\Tests\Integration\Routing\CategoryBackedEnum;

Route::get('/categories/{category}', function (CategoryBackedEnum \$category) {
    return \$category->value;
})->middleware('web');

Route::get('/animals/{animal}', function (AnimalBackedEnum \$animal) {
    return \$animal->value;
})->middleware('web');
PHP);

        $response = $this->get('/categories/fruits');
        $response->assertSee('fruits');

        $response = $this->get('/categories/people');
        $response->assertSee('people');

        $response = $this->get('/categories/cars');
        $response->assertNotFound(404);

        $response = $this->get('/animals/0');
        $response->assertSee(0);

        $response = $this->get('/animals/1');
        $response->assertSee(1);

        $response = $this->get('/animals/2');
        $response->assertNotFound(404);
    }

    public function testWithoutRouteCachingEnabled()
    {
        config(['app.key' => str_repeat('a', 32)]);

        Route::post('/categories/{category}', function (CategoryBackedEnum $category) {
            return $category->value;
        })->middleware(['web']);

        $response = $this->post('/categories/fruits');
        $response->assertSee('fruits');

        $response = $this->post('/categories/people');
        $response->assertSee('people');

        $response = $this->post('/categories/cars');
        $response->assertNotFound(404);

        Route::post('/animals/{animal}', function (AnimalBackedEnum $animal) {
            return $animal->value;
        })->middleware(['web']);

        $response = $this->post('/animals/0');
        $response->assertSee(0);

        $response = $this->post('/animals/1');
        $response->assertSee(1);

        $response = $this->post('/animals/2');
        $response->assertNotFound(404);
    }
}
