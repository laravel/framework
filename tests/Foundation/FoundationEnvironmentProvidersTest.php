<?php

use Illuminate\Config\Repository;
use Mockery as m;

class FoundationEnvironmentProvidersTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testMergeProvidersWithEnvironmentProviders()
    {
        $app = m::mock('\Illuminate\Foundation\Application[registerProviders]');
        $app->shouldReceive('registerProviders')->once()->with(['foobar','foobar-local']);

        $app['config'] = $this->getConfigRepository();
        $app['env'] = 'testing';
        $app->registerConfiguredProviders();
    }

    public function testEnvironmentProvidersAreOptional()
    {
        $app = m::mock('\Illuminate\Foundation\Application[registerProviders]');
        $app->shouldReceive('registerProviders')->twice()->with(['foobar']);

        $app['config'] = $this->getConfigRepository();
        $app['env'] = 'unknown';
        $app->registerConfiguredProviders();

        $app['env'] = 'empty';
        $app->registerConfiguredProviders();
    }

    private function getConfigRepository()
    {
        return new Repository([
            'app' => [
                'providers' => [
                    'foobar'
                ],
                'providers-testing' => [
                    'foobar-local'
                ],
                'providers-empty' => [],
            ]
        ]);
    }

}
