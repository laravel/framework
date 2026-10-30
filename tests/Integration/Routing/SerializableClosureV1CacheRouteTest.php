<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

use function Illuminate\Filesystem\join_paths;

#[RequiresOperatingSystem('Linux|Darwin')]
#[WithConfig('app.key', 'AckfSECXIvnK5r28GVIWUAxmbBSjTsmF')]
#[WithMigration]
class SerializableClosureV1CacheRouteTest extends TestCase
{
    use RefreshDatabase;

    /** {@inheritDoc} */
    #[\Override]
    protected function getPackageProviders($app)
    {
        return [
            \Illuminate\Foundation\Support\Providers\RouteServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        $_ENV['APP_ROUTES_CACHE'] = realpath(join_paths(dirname(__DIR__, 2), 'Routing', 'Fixtures', 'serializable-closure-v1', 'routes-v7.php'));

        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($_ENV['APP_ROUTES_CACHE']);
    }

    public function testItCanUseCachedRouteFromSerializableClosureV1()
    {
        $user = UserFactory::new()->create();

        $this->assertTrue($this->app->routesAreCached());

        $this->get('/')->assertSee('Laravel');

        $this->get("/users/{$user->getKey()}")
            ->assertJson($user->toArray());
    }
}
