<?php

namespace Illuminate\Tests\Integration\Filesystem;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

#[WithConfig('filesystems.disks.local.serve', true)]
class ServeFileTest extends TestCase
{
    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            Storage::put('serve-file-test.txt', 'Hello World');
        });

        $this->beforeApplicationDestroyed(function () {
            Storage::delete('serve-file-test.txt');
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

    public function testItCanServeAFileWithSpecialCharacters()
    {
        Storage::put('serve file test.txt', 'Hello World');

        $url = Storage::temporaryUrl('serve file test.txt', Carbon::now()->addMinute());

        $response = $this->get($url);

        $this->assertSame('Hello World', $response->streamedContent());

        Storage::delete('serve file test.txt');
    }
}
