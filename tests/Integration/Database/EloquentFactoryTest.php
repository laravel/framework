<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Eloquent\Model;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class EloquentFactoryTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
        $app->useDatabasePath(__DIR__);
    }

    public function testLoad()
    {
        /** @var Factory $factory */
        $factory = app(Factory::class);

        $this->assertTrue($factory->offsetExists(FactoryViaVariable::class));
        $this->assertTrue($factory->offsetExists(FactoryViaFacade::class));
        $this->assertTrue($factory->offsetExists(FactoryViaExtension::class));
        $this->assertFalse($factory->offsetExists(FactoryNotDefined::class));

        $this->assertEquals(['name' => 'variable'], $factory->raw(FactoryViaVariable::class));
        $this->assertEquals(['name' => 'facade'], $factory->raw(FactoryViaFacade::class));
        $this->assertEquals(['name' => 'facade', 'extends' => FactoryViaFacade::class], $factory->raw(FactoryViaExtension::class));

        // These tests should fail but pass due to how factories are loaded.
        $this->assertTrue($factory->offsetExists(FactoryViaThis::class));
        $this->assertEquals(['name' => 'this', 'protected' => 4], $factory->raw(FactoryViaThis::class));
    }
}

class FactoryViaVariable extends Model
{
}

class FactoryViaFacade extends Model
{
}

class FactoryViaExtension extends Model
{
}

class FactoryViaThis extends Model
{
}

class FactoryNotDefined extends Model
{
}
