<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;
use PHPUnit\Framework\Attributes\TestWith;

#[WithConfig('filesystems.disks.local.serve', true)]
class ServeFileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Storage::put('serve-file-test.txt', 'Hello World');
            Storage::put('serve-file-test.txt?pad=x', 'Hello Question');
            Storage::put('nested/folder/serve-file-test.txt', 'Hello Nested');
        });

        $this->beforeApplicationDestroyed(function () {
            Storage::delete([
                'serve-file-test.txt',
                'serve-file-test.txt?pad=x',
                'nested/folder/serve-file-test.txt',
            ]);
        });

        parent::setUp();
    }

    public function testItCanServeAnExistingFile()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt', now()->addMinutes(1));

        $response = $this->get($url);

        $this->assertEquals('Hello World', $response->streamedContent());
    }

    #[WithConfig('app.url', 'https://app.example')]
    #[TestWith([[]])]
    #[TestWith([['.*']])]
    public function testTemporaryUrlsIgnoreForgedHost(array $trustedHosts)
    {
        Request::setTrustedHosts($trustedHosts);
        $this->beforeApplicationDestroyed(fn () => Request::setTrustedHosts([]));
        $this->app['router']->get('security-file-urls', function () {
            return [
                'host' => request()->getHost(),
                'download' => Storage::temporaryUrl('serve-file-test.txt', now()->addMinutes(1)),
                'upload' => Storage::temporaryUploadUrl('serve-file-test.txt', now()->addMinutes(1))['url'],
            ];
        });

        $response = $this->get('http://attacker.example/security-file-urls');
        $response->assertOk();
        $response->assertJsonPath('host', 'attacker.example');

        $downloadUrl = $response->json('download');
        $uploadUrl = $response->json('upload');
        $this->assertStringStartsWith('https://app.example/', $downloadUrl);
        $this->assertStringStartsWith('https://app.example/', $uploadUrl);
        $this->assertSame('Hello World', $this->get($downloadUrl)->streamedContent());
        $this->call('PUT', $uploadUrl, content: 'Updated content')->assertNoContent();
        $this->assertSame('Updated content', Storage::get('serve-file-test.txt'));
    }

    public function testItWill404OnMissingFile()
    {
        $url = Storage::temporaryUrl('serve-missing-test.txt', now()->addMinutes(1));

        $response = $this->get($url);

        $response->assertNotFound();
    }

    public function testItWill403OnWrongSignature()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt', now()->addMinutes(1));

        $url = $url.'c';

        $response = $this->get($url);

        $response->assertForbidden();
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testItCanServeAFileWithUriDelimitersInThePath()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt?pad=x', Carbon::now()->addMinute());

        $response = $this->get($url);

        $this->assertSame('Hello Question', $response->streamedContent());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testTemporaryUrlPreservesPathSeparatorsInNestedPaths()
    {
        $url = Storage::temporaryUrl('nested/folder/serve-file-test.txt', Carbon::now()->addMinute());

        $this->assertStringContainsString('nested/folder/serve-file-test.txt', $url);

        $response = $this->get($url);

        $this->assertSame('Hello Nested', $response->streamedContent());
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testUriDelimitersInThePathCannotHideAnExpiredUrl()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt?pad=x', Carbon::now()->subMinute());

        $response = $this->get($url);

        $response->assertForbidden();
    }
}
