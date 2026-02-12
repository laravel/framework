<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('filesystems.disks.local.serve', true)]
class ReceiveFileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            Storage::delete('receive-file-test.txt');
        });

        parent::setUp();
    }

    public function testItCanReceiveAFile()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', now()->addMinutes(1));

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello World');

        $response->assertNoContent();
        Storage::assertExists('receive-file-test.txt', 'Hello World');
    }

    public function testItWill403OnWrongSignature()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', now()->addMinutes(1));

        $url = $result['url'].'c';

        $response = $this->call('PUT', $url, [], [], [], [], 'Hello World');

        $response->assertForbidden();
        Storage::assertMissing('receive-file-test.txt');
    }

    public function testItWill403OnExpiredUrl()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', now()->subMinutes(1));

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello World');

        $response->assertForbidden();
        Storage::assertMissing('receive-file-test.txt');
    }

    public function testDownloadUrlCannotBeUsedForUpload()
    {
        Storage::put('receive-file-test.txt', 'Original Content');

        $downloadUrl = Storage::temporaryUrl('receive-file-test.txt', now()->addMinutes(1));

        $response = $this->call('PUT', $downloadUrl, [], [], [], [], 'Malicious Content');

        $response->assertForbidden();
        $this->assertSame('Original Content', Storage::get('receive-file-test.txt'));
    }

    public function testUploadUrlCannotBeUsedForDownload()
    {
        Storage::put('receive-file-test.txt', 'Secret Content');

        $uploadUrl = Storage::temporaryUploadUrl('receive-file-test.txt', now()->addMinutes(1));

        $response = $this->get($uploadUrl['url']);

        $response->assertForbidden();
    }
}
