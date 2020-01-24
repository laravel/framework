<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\FileFactory;
use PHPUnit\Framework\TestCase;

class HttpTestingFileFactoryTest extends TestCase
{
    public function testImagePng()
    {
        if (! function_exists('imagepng')) {
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without PNG support.');
        }

        $image = (new FileFactory)->image('test.png', 15, 20);

        $info = getimagesize($image->getRealPath());

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
    }

    public function testImageJpeg()
    {
        if (! function_exists('imagejpeg')) {
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without JPEG support.');
        }

        $image = (new FileFactory)->image('test.jpeg', 15, 20);

        $info = getimagesize($image->getRealPath());

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
    }

    public function testCreateWithMimeType()
    {
        $this->assertSame(
            'audio/webm',
            (new FileFactory)->create('someaudio.webm', 0, 'audio/webm')->getMimeType()
        );
    }

    public function testCreateWithoutMimeType()
    {
        $this->assertSame(
            'video/webm',
            (new FileFactory)->create('someaudio.webm')->getMimeType()
        );
    }
}
