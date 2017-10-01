<?php

namespace Illuminate\Tests\Integration\Database\QueryBuilderOverrideSelectTest;

use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class EloquentWithCountTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }

    /**
     * @test
     */
    public function test_query_builder_can_clear_bindings_when_override_select_columns()
    {
        $query = app('Illuminate\Database\Query\Builder')
            ->selectRaw('(select count(*) from posts where status = ?)', [1])
            ->from('users');

        $this->assertEquals([1], $query->getBindings());

        $query = $query->select()->where('foo', 'bar');

        $this->assertEquals(['bar'], $query->getBindings());
    }
}
