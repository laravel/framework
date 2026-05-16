<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[WithConfig('filesystems.disks.local.serve', true)]
class ReceiveFileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->beforeApplicationDestroyed(function () {
            Storage::delete([
                'receive-file-test.txt',
                'receive-file-test.txt?pad=x',
            ]);
        });

        parent::setUp();
    }

    public function testItCanReceiveAFile()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', Carbon::now()->addMinute());

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello World');

        $response->assertNoContent();
        Storage::assertExists('receive-file-test.txt', 'Hello World');
    }

    public function testItWill403OnWrongSignature()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', Carbon::now()->addMinute());

        $url = $result['url'].'c';

        $response = $this->call('PUT', $url, [], [], [], [], 'Hello World');

        $response->assertForbidden();
        Storage::assertMissing('receive-file-test.txt');
    }

    public function testItWill403OnExpiredUrl()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt', Carbon::now()->subMinute());

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello World');

        $response->assertForbidden();
        Storage::assertMissing('receive-file-test.txt');
    }

    public function testDownloadUrlCannotBeUsedForUpload()
    {
        Storage::put('receive-file-test.txt', 'Original Content');

        $downloadUrl = Storage::temporaryUrl('receive-file-test.txt', Carbon::now()->addMinute());

        $response = $this->call('PUT', $downloadUrl, [], [], [], [], 'Malicious Content');

        $response->assertForbidden();
        $this->assertSame('Original Content', Storage::get('receive-file-test.txt'));
    }

    public function testUploadUrlCannotBeUsedForDownload()
    {
        Storage::put('receive-file-test.txt', 'Secret Content');

        $uploadUrl = Storage::temporaryUploadUrl('receive-file-test.txt', Carbon::now()->addMinute());

        $response = $this->get($uploadUrl['url']);

        $response->assertForbidden();
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testItCanReceiveAFileWithUriDelimitersInThePath()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt?pad=x', Carbon::now()->addMinute());

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello Question');

        $response->assertNoContent();
        Storage::assertExists('receive-file-test.txt?pad=x', 'Hello Question');
        Storage::assertMissing('receive-file-test.txt');
    }

    #[RequiresOperatingSystem('Linux|Darwin')]
    public function testUriDelimitersInThePathCannotHideAnExpiredUploadUrl()
    {
        $result = Storage::temporaryUploadUrl('receive-file-test.txt?pad=x', Carbon::now()->subMinute());

        $response = $this->call('PUT', $result['url'], [], [], [], [], 'Hello Question');

        $response->assertForbidden();
        Storage::assertMissing('receive-file-test.txt');
        Storage::assertMissing('receive-file-test.txt?pad=x');
    }
}
