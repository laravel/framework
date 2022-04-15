<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    public function testDefaultProviders()
    {
        $defaultProviders = ServiceProvider::defaultProviders();

        $this->assertInstanceOf(Collection::class, $defaultProviders);

        foreach ($defaultProviders as $provider) {
            $this->assertTrue(class_exists($provider));
            $this->assertTrue(is_subclass_of($provider, ServiceProvider::class));
        }
    }
}
