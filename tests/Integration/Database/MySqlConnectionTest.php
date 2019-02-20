<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class MySqlConnectionTest extends DatabaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'username' => 'root',
            'password' => '',
            'database' => 'forge',
            'prefix' => '',
            'options' => [
                \PDO::ATTR_EMULATE_PREPARES => true,
            ],
        ]);
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('posts', function ($table) {
            $table->decimal('rating', 5, 1);
        });
    }

    public function tearDown()
    {
        Schema::drop('posts');

        parent::tearDown();
    }

    public function testFloatBindingsMaintainDecimals()
    {
        DB::table('posts')->insert([
            'rating' => 5.2,
        ]);

        $this->assertEquals(5.2, DB::table('posts')->value('rating'));
    }
}
