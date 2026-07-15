<?php

namespace Illuminate\Tests\Image;

use Illuminate\Image\ImageOrigin;
use PHPUnit\Framework\TestCase;

class ImageOriginTest extends TestCase
{
    public function test_to_string_with_type_only()
    {
        $this->assertSame('bytes', (string) new ImageOrigin('bytes'));
    }

    public function test_to_string_with_reference()
    {
        $this->assertSame('path:/tmp/photo.jpg', (string) new ImageOrigin('path', '/tmp/photo.jpg'));
    }

    public function test_to_string_with_disk_and_reference()
    {
        $this->assertSame('storage:s3:photos/avatar.jpg', (string) new ImageOrigin('storage', 'photos/avatar.jpg', 's3'));
    }
}
