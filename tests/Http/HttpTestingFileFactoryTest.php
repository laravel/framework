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

        $jpeg = (new FileFactory)->image('test.jpeg', 15, 20);
        $jpg = (new FileFactory)->image('test.jpg');

        $info = getimagesize($jpeg->getRealPath());

        $this->assertSame('image/jpeg', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
        $this->assertSame(
            'image/jpeg',
            mime_content_type($jpg->getRealPath())
        );
    }

    public function testImageGif()
    {
        if (! function_exists('imagegif'))
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without GIF support.');

        $image = (new FileFactory)->image('test.gif');

        $this->assertSame(
            'image/gif',
            mime_content_type($image->getRealPath())
        );
    }

    public function testImageWebp()
    {
        if (! function_exists('imagewebp'))
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without WEBP support.');

        $image = (new FileFactory)->image('test.webp');

        $this->assertSame(
            'image/webp',
            mime_content_type($image->getRealPath())
        );
    }

    public function testImageWbmp()
    {
        if (! function_exists('imagewbmp'))
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without WBMP support.');

        $image = (new FileFactory)->image('test.wbmp');

        $this->assertSame(
            'image/vnd.wap.wbmp',
            getimagesize($image->getRealPath())['mime']
        );
    }

    public function testImageBmp()
    {
        if (! function_exists('imagebmp'))
            $this->markTestSkipped('The extension gd is missing from your system or was compiled without BMP support.');

        $image = (new FileFactory)->image('test.bmp');

        $imagePath = $image->getRealPath();

        $this->assertSame('image/x-ms-bmp', mime_content_type($imagePath));

        $this->assertSame('image/bmp', getimagesize($imagePath)['mime']);
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
