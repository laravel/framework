<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use JMac\Testing\Double;
use PHPUnit\Framework\TestCase;

class FoundationProviderRepositoryTest extends TestCase
{
    public function testServicesAreRegisteredWhenManifestIsNotRecompiled()
    {
        $app = new Application;

        $base = tempnam(sys_get_temp_dir(), 'services');
        $manifestPath = $base.'.php';
        file_put_contents($manifestPath, '<?php return '.var_export([
            'eager' => [RepositoryTestEagerProviderStub::class],
            'deferred' => ['deferred.service' => RepositoryTestDeferredProviderStub::class],
            'providers' => [RepositoryTestEagerProviderStub::class],
            'when' => [],
        ], true).';');

        $repo = new ProviderRepository($app, new Filesystem, $manifestPath);

        $repo->load([RepositoryTestEagerProviderStub::class]);

        $this->assertInstanceOf(RepositoryTestEagerProviderStub::class, $app->getProvider(RepositoryTestEagerProviderStub::class));
        $this->assertSame(['deferred.service' => RepositoryTestDeferredProviderStub::class], $app->getDeferredServices());

        unlink($manifestPath);
        unlink($base);
    }

    public function testManifestIsProperlyRecompiled()
    {
        $app = new Application;

        $base = tempnam(sys_get_temp_dir(), 'services');
        $manifestPath = $base.'.php';

        $repo = new ProviderRepository($app, new Filesystem, $manifestPath);

        $repo->load([RepositoryTestDeferredProviderStub::class, RepositoryTestEagerProviderStub::class]);

        $this->assertInstanceOf(RepositoryTestEagerProviderStub::class, $app->getProvider(RepositoryTestEagerProviderStub::class));
        $this->assertNull($app->getProvider(RepositoryTestDeferredProviderStub::class));
        $this->assertSame([
            'foo.provides1' => RepositoryTestDeferredProviderStub::class,
            'foo.provides2' => RepositoryTestDeferredProviderStub::class,
        ], $app->getDeferredServices());

        $written = include $manifestPath;
        $this->assertSame([RepositoryTestDeferredProviderStub::class, RepositoryTestEagerProviderStub::class], $written['providers']);
        $this->assertSame([RepositoryTestEagerProviderStub::class], $written['eager']);

        unlink($manifestPath);
        unlink($base);
    }

    public function testShouldRecompileReturnsCorrectValue()
    {
        $repo = new ProviderRepository(new Application, new Filesystem, __DIR__.'/services.php');
        $this->assertTrue($repo->shouldRecompile(null, []));
        $this->assertTrue($repo->shouldRecompile(['providers' => ['foo']], ['foo', 'bar']));
        $this->assertFalse($repo->shouldRecompile(['providers' => ['foo']], ['foo']));
    }

    public function testLoadManifestReturnsParsedJSON()
    {
        $base = tempnam(sys_get_temp_dir(), 'services');
        $manifestPath = $base.'.php';
        $array = ['users' => ['dayle' => true], 'when' => []];
        file_put_contents($manifestPath, '<?php return '.var_export($array, true).';');

        $repo = new ProviderRepository(new Application, new Filesystem, $manifestPath);

        $this->assertEquals($array, $repo->loadManifest());

        unlink($manifestPath);
        unlink($base);
    }

    public function testWriteManifestStoresToProperLocation()
    {
        $base = tempnam(sys_get_temp_dir(), 'services');
        $manifestPath = $base.'.php';

        $repo = new ProviderRepository(new Application, new Filesystem, $manifestPath);

        $result = $repo->writeManifest(['foo']);

        $this->assertEquals(['foo', 'when' => []], $result);
        $this->assertSame('<?php return '.var_export(['foo'], true).';', file_get_contents($manifestPath));

        unlink($manifestPath);
        unlink($base);
    }

    public function testWriteManifestThrowsExceptionIfManifestDirDoesntExist()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^The (.*) directory must be present and writable.$/');

        $files = Double::for(Filesystem::class);
        $files->expects('replace')->never();
        $repo = new ProviderRepository(Double::for(ApplicationContract::class, override: true)->instance(), $files, __DIR__.'/cache/services.php');

        $repo->writeManifest(['foo']);
    }
}

class RepositoryTestEagerProviderStub extends ServiceProvider
{
    public function register()
    {
    }
}

class RepositoryTestDeferredProviderStub extends ServiceProvider implements DeferrableProvider
{
    public function register()
    {
    }

    public function provides()
    {
        return ['foo.provides1', 'foo.provides2'];
    }
}
