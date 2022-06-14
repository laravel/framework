<?php

namespace Illuminate\Tests\Foundation\Support;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Support\Providers\WithPolicies;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class WithPoliciesTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        m::close();
    }

    public function testWithPoliciesWillRegisterPolicies()
    {
        $gateMock = Gate::partialMock();
        $gateMock->shouldReceive('policy')->once()->withArgs(['testClass1', 'testPolicy1']);
        $gateMock->shouldReceive('policy')->once()->withArgs(['testClass2', 'testPolicy2']);

        $app = new Application();
        $app->register(new ServiceProviderWithPoliciesStub($app));
    }
}

class ServiceProviderWithPoliciesStub extends ServiceProvider
{
    use WithPolicies;

    public $policies = [
        'testClass1' => 'testPolicy1',
        'testClass2' => 'testPolicy2',
    ];
}
