<?php

namespace Illuminate\Tests\Integration\Support;

use Illuminate\Tests\Integration\Support\Fixtures\MultipleInstanceManager;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class MultipleInstanceManagerTest extends TestCase
{
    public function test_configurable_instances_can_be_resolved()
    {
        $manager = new MultipleInstanceManager($this->app);

        $fooInstance = $manager->instance('foo');
        $this->assertSame('option-value', $fooInstance->config['foo-option']);

        $barInstance = $manager->instance('bar');
        $this->assertSame('option-value', $barInstance->config['bar-option']);

        $mysqlInstance = $manager->instance('mysql_database-connection');
        $this->assertSame('option-value', $mysqlInstance->config['mysql_database-connection-option']);

        $duplicateFooInstance = $manager->instance('foo');
        $duplicateBarInstance = $manager->instance('bar');
        $duplicateMysqlInstance = $manager->instance('mysql_database-connection');
        $this->assertEquals(spl_object_id($fooInstance), spl_object_id($duplicateFooInstance));
        $this->assertEquals(spl_object_id($barInstance), spl_object_id($duplicateBarInstance));
        $this->assertEquals(spl_object_id($mysqlInstance), spl_object_id($duplicateMysqlInstance));
    }

    public function test_unresolvable_instances_throw_errors()
    {
        $this->expectException(RuntimeException::class);

        $manager = new MultipleInstanceManager($this->app);

        $instance = $manager->instance('missing');
    }

    public function test_custom_driver_closure_bound_object_is_multiple_instance_manager()
    {
        $manager = new MultipleInstanceManager($this->app);
        $manager->extend('custom', fn () => $this);
        $this->assertSame($manager, $manager->instance('custom'));
    }
}
