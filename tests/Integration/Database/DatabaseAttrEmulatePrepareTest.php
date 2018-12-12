<?php

namespace Illuminate\Tests\Integration\Database;

use PDO;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class DatabaseAttrEmulatePrepareTest extends Testcase
{
    public function setUp()
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->increments('id');
            $table->double('price', 12, 3);
            $table->timestamps();
        });
    }

    public function tearDown()
    {
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
            'prefix' => '',
            'options' => [
                PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);
    }

    public function test_float_is_saved_correctly_when_attr_emulate_prepares_is_true()
    {
        $product = Product::forceCreate([
            'price' => 1.234,
        ])->fresh();

        $this->assertEquals(1.234, $product->price);
    }
}

class Product extends Model
{
    protected $table = 'products';
}