<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresOperatingSystem;

#[WithConfig('filesystems.disks.local.serve', true)]
class ServeFileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Storage::put('serve-file-test.txt', 'Hello World');
            Storage::put('serve-file-test.txt?pad=x', 'Hello Question');
        });

        $this->beforeApplicationDestroyed(function () {
            Storage::delete([
                'serve-file-test.txt',
                'serve-file-test.txt?pad=x',
            ]);
        });

        parent::setUp();
    }

    public function testItCanServeAnExistingFile()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt', Carbon::now()->addMinute());

        $response = $this->get($url);

        $this->assertSame('Hello World', $response->streamedContent());
    }

    public function testItWill404OnMissingFile()
    {
        $url = Storage::temporaryUrl('serve-missing-test.txt', Carbon::now()->addMinute());

        $response = $this->get($url);

        $response->assertNotFound();
    }

    public function testItWill403OnWrongSignature()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt', Carbon::now()->addMinute());

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
    public function testUriDelimitersInThePathCannotHideAnExpiredUrl()
    {
        $url = Storage::temporaryUrl('serve-file-test.txt?pad=x', Carbon::now()->subMinute());

        $response = $this->get($url);

        $response->assertForbidden();
    }
}
