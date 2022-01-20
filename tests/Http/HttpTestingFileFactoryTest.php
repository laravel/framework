<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Testing\FileFactory;
use PHPUnit\Framework\TestCase;

/**
 * @requires extension gd
 *
 * @link https://www.php.net/manual/en/function.gd-info.php
 */
class HttpTestingFileFactoryTest extends TestCase
{
    public function testImagePng()
    {
        if (! $this->isGDSupported('PNG Support')) {
            $this->markTestSkipped('Requires PNG support.');
        }

        $image = (new FileFactory)->image('test.png', 15, 20);

        $info = getimagesize($image->getRealPath());

        $this->assertSame('image/png', $info['mime']);
        $this->assertSame(15, $info[0]);
        $this->assertSame(20, $info[1]);
    }

    public function testImageJpeg()
    {
        if (! $this->isGDSupported('JPEG Support')) {
            $this->markTestSkipped('Requires JPEG support.');
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
        if (! $this->isGDSupported('GIF Create Support')) {
            $this->markTestSkipped('Requires GIF Create support.');
        }

        $image = (new FileFactory)->image('test.gif');

        $this->assertSame(
            'image/gif',
            mime_content_type($image->getRealPath())
        );
    }

    public function testImageWebp()
    {
        if (! $this->isGDSupported('WebP Support')) {
            $this->markTestSkipped('Requires Webp support.');
        }

        $image = (new FileFactory)->image('test.webp');

        $this->assertSame(
            'image/webp',
            mime_content_type($image->getRealPath())
        );
    }

    public function testImageWbmp()
    {
        if (! $this->isGDSupported('WBMP Support')) {
            $this->markTestSkipped('Requires WBMP support.');
        }

        $image = (new FileFactory)->image('test.wbmp');

        $this->assertSame(
            'image/vnd.wap.wbmp',
            getimagesize($image->getRealPath())['mime']
        );
    }

    public function testImageBmp()
    {
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

    /**
     * @param  string  $driver
     * @return bool
     */
    private function isGDSupported(string $driver = 'GD Version'): bool
    {
        $gdInfo = gd_info();

        if (isset($gdInfo[$driver])) {
            return $gdInfo[$driver];
        }

        return false;
    }
}
