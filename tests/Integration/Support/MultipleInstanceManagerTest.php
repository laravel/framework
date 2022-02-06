<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\MultipleInstanceManager;
use Orchestra\Testbench\TestCase;

class MultipleInstanceManagerTest extends TestCase
{
    public function test_configurable_instances_can_be_resolved()
    {
        $manager = new MultipleInstanceManager($this->app);

        $fooInstance = $manager->instance('foo');
        $this->assertEquals('option-value', $fooInstance->config['foo-option']);

        $barInstance = $manager->instance('bar');
        $this->assertEquals('option-value', $barInstance->config['bar-option']);

        $duplicateFooInstance = $manager->instance('foo');
        $duplicateBarInstance = $manager->instance('bar');
        $this->assertEquals(spl_object_hash($fooInstance), spl_object_hash($duplicateFooInstance));
        $this->assertEquals(spl_object_hash($barInstance), spl_object_hash($duplicateBarInstance));
    }

    public function test_unresolvable_isntances_throw_errors()
    {
        $this->expectException(\RuntimeException::class);

        $manager = new MultipleInstanceManager($this->app);

        $instance = $manager->instance('missing');
    }
}
