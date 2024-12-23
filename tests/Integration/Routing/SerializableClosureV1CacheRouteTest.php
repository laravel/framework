<?php

namespace Illuminate\Tests\Integration\Route;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystemFamily;

use function Illuminate\Filesystem\join_paths;

#[RequiresOperatingSystemFamily('Linux|Darwin')]
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

    /** {@inheritDoc} */
    #[\Override]
    protected function setUp(): void
    {
        $_ENV['APP_ROUTES_CACHE'] = realpath(join_paths(__DIR__, 'stubs', 'serializable-closure-v1', 'routes-v7.php'));

        parent::setUp();
    }

    /** {@inheritDoc} */
    #[\Override]
    protected function tearDown(): void
    {
        unset($_ENV['APP_ROUTES_CACHE']);

        parent::tearDown();
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
