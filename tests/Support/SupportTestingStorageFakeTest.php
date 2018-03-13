<?php

namespace Illuminate\Tests\Support;

use PHPUnit\Framework\TestCase;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Storage;
use Illuminate\Filesystem\FilesystemManager;
use PHPUnit\Framework\ExpectationFailedException;

class StorageFakeTest extends TestCase
{
    protected $fake;

    protected function setUp()
    {
        parent::setUp();
        $app = new Application;
        $app['path.storage'] = __DIR__;
        $app['filesystem'] = new FilesystemManager($app);
        Storage::setFacadeApplication($app);
        Storage::fake('testing');
        $this->fake = Storage::disk('testing');
    }

    public function testAssertExists()
    {
        $this->expectException(ExpectationFailedException::class);

        $this->fake->assertExists('letter.txt');
    }

    public function testAssertMissing()
    {
        $this->fake->put('letter.txt', 'hi');

        $this->expectException(ExpectationFailedException::class);

        $this->fake->assertMissing('letter.txt');
    }
}
