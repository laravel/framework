<?php

namespace Illuminate\Tests\Foundation;

use Exception;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\ServiceProvider;
use Mockery;
use PHPUnit\Framework\TestCase;

class FoundationProviderRepositoryTest extends TestCase
{
    public function testServicesAreRegisteredWhenManifestIsNotRecompiled()
    {
        $app = Mockery::mock(Application::class);

        $repo = Mockery::mock(ProviderRepository::class.'[createProvider,loadManifest,shouldRecompile]', [$app, Mockery::mock(Filesystem::class), [__DIR__.'/services.php']]);
        $repo->expects('loadManifest')->andReturn(['eager' => ['foo'], 'deferred' => ['deferred'], 'providers' => ['providers'], 'when' => []]);
        $repo->expects('shouldRecompile')->andReturn(false);

        $app->expects('register')->with('foo');
        $app->expects('addDeferredServices')->with(['deferred']);

        $repo->load([]);
    }

    public function testManifestIsProperlyRecompiled()
    {
        $app = Mockery::mock(Application::class);

        $repo = Mockery::mock(ProviderRepository::class.'[createProvider,loadManifest,writeManifest,shouldRecompile]', [$app, Mockery::mock(Filesystem::class), [__DIR__.'/services.php']]);

        $repo->expects('loadManifest')->andReturn(['eager' => [], 'deferred' => ['deferred']]);
        $repo->expects('shouldRecompile')->andReturn(true);

        // foo mock is just a deferred provider
        $fooMock = Mockery::mock(ServiceProvider::class);
        $repo->expects('createProvider')->with('foo')->andReturn($fooMock);
        $fooMock->expects('isDeferred')->andReturn(true);
        $fooMock->expects('provides')->andReturn(['foo.provides1', 'foo.provides2']);
        $fooMock->expects('when')->andReturn([]);

        // bar mock is added to eagers since it's not reserved
        $barMock = Mockery::mock(ServiceProvider::class);
        $repo->expects('createProvider')->with('bar')->andReturn($barMock);
        $barMock->expects('isDeferred')->andReturn(false);
        $repo->expects('writeManifest')->andReturnUsing(function ($manifest) {
            return $manifest;
        });

        $app->expects('register')->with('bar');
        $app->expects('addDeferredServices')->with(['foo.provides1' => 'foo', 'foo.provides2' => 'foo']);

        $repo->load(['foo', 'bar']);
    }

    public function testShouldRecompileReturnsCorrectValue()
    {
        $repo = new ProviderRepository(Mockery::mock(ApplicationContract::class), new Filesystem, __DIR__.'/services.php');
        $this->assertTrue($repo->shouldRecompile(null, []));
        $this->assertTrue($repo->shouldRecompile(['providers' => ['foo']], ['foo', 'bar']));
        $this->assertFalse($repo->shouldRecompile(['providers' => ['foo']], ['foo']));
    }

    public function testLoadManifestReturnsParsedJSON()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->expects('exists')->with(__DIR__.'/services.php')->andReturn(true);
        $files->expects('getRequire')->with(__DIR__.'/services.php')->andReturn($array = ['users' => ['dayle' => true], 'when' => []]);
        $repo = new ProviderRepository(Mockery::mock(ApplicationContract::class), $files, __DIR__.'/services.php');

        $this->assertEquals($array, $repo->loadManifest());
    }

    public function testWriteManifestStoresToProperLocation()
    {
        $files = Mockery::mock(Filesystem::class);
        $files->expects('replace')->with(__DIR__.'/services.php', '<?php return '.var_export(['foo'], true).';');
        $repo = new ProviderRepository(Mockery::mock(ApplicationContract::class), $files, __DIR__.'/services.php');

        $result = $repo->writeManifest(['foo']);

        $this->assertEquals(['foo', 'when' => []], $result);
    }

    public function testWriteManifestThrowsExceptionIfManifestDirDoesntExist()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^The (.*) directory must be present and writable.$/');

        $files = Mockery::mock(Filesystem::class);
        $files->shouldReceive('replace')->never();
        $repo = new ProviderRepository(Mockery::mock(ApplicationContract::class), $files, __DIR__.'/cache/services.php');

        $repo->writeManifest(['foo']);
    }
}
