<?php

namespace Illuminate\Tests\Integration\Foundation\Image;

use Illuminate\Foundation\Image\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('gd')]
class ImageTest extends TestCase
{
    public function test_cover_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(200, 200));

        $result = $image->cover(100, 100)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_scale_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(400, 200));

        $result = $image->scale(200, 200)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(100, $height);
    }

    public function test_to_png_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toPng()->toBytes();

        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($result)[2]);
    }

    public function test_to_webp_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toWebp()->toBytes();

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_to_gif_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toGif()->toBytes();

        $this->assertSame(IMAGETYPE_GIF, getimagesizefromstring($result)[2]);
    }

    public function test_blur_and_to_bytes()
    {
        $contents = $this->fakeImageContents(100, 100);
        $image = new Image($contents);

        $result = $image->blur(10)->toBytes();

        $this->assertNotSame($contents, $result);
    }

    public function test_greyscale_and_to_bytes()
    {
        $contents = $this->fakeImageContents(100, 100);
        $image = new Image($contents);

        $result = $image->greyscale()->toBytes();

        $this->assertNotSame($contents, $result);
    }

    public function test_immutability_with_variants()
    {
        $image = new Image($this->fakeImageContents(400, 400));

        $thumb = $image->cover(100, 100)->toPng();
        $large = $image->scale(200, 200)->toWebp();

        $thumbBytes = $thumb->toBytes();
        $largeBytes = $large->toBytes();

        $thumbSize = getimagesizefromstring($thumbBytes);
        $largeSize = getimagesizefromstring($largeBytes);

        $this->assertSame(100, $thumbSize[0]);
        $this->assertSame(100, $thumbSize[1]);
        $this->assertSame(IMAGETYPE_PNG, $thumbSize[2]);

        $this->assertSame(200, $largeSize[0]);
        $this->assertSame(200, $largeSize[1]);
        $this->assertSame(IMAGETYPE_WEBP, $largeSize[2]);
    }

    public function test_store_saves_to_disk()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toPng()->store('images', 'local');

        $files = Storage::disk('local')->files('images');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.png', $files[0]);
    }

    public function test_store_as_saves_with_custom_name()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toPng()->storeAs('images', 'avatar.png', 'local');

        Storage::disk('local')->assertExists('images/avatar.png');
    }

    public function test_mime_type_after_format_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $this->assertSame('image/png', $image->toPng()->mimeType());
    }

    public function test_extension_after_format_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $this->assertSame('png', $image->toPng()->extension());
        $this->assertSame('jpg', $image->extension());
    }

    public function test_dimensions_after_cover()
    {
        $image = new Image($this->fakeImageContents(400, 300));

        $this->assertSame([200, 200], $image->cover(200, 200)->dimensions());
        $this->assertSame([400, 300], $image->dimensions());
    }

    public function test_quality_affects_file_size()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $low = $image->toJpg()->quality(1)->toBytes();
        $high = $image->toJpg()->quality(100)->toBytes();

        $this->assertLessThan(strlen($high), strlen($low));
    }

    public function test_full_avatar_pipeline()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(800, 600));

        $result = $image->orient()->cover(200, 200)->toWebp()->quality(80);
        $result->store('avatars', 'local');

        $this->assertSame([200, 200], $result->dimensions());
        $this->assertSame('image/webp', $result->mimeType());

        $files = Storage::disk('local')->files('avatars');
        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.webp', $files[0]);
    }

    public function test_two_variants_from_uploaded_file()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $image = new Image(fn () => $file->getContent(), $file);

        $thumb = $image->cover(100, 100)->toWebp();
        $large = $image->scale(400, 400)->toPng();

        $thumb->store('thumbs', 'local');
        $large->store('photos', 'local');

        $thumbFiles = Storage::disk('local')->files('thumbs');
        $largeFiles = Storage::disk('local')->files('photos');

        $this->assertCount(1, $thumbFiles);
        $this->assertCount(1, $largeFiles);

        $thumbBytes = Storage::disk('local')->get($thumbFiles[0]);
        $largeBytes = Storage::disk('local')->get($largeFiles[0]);

        $thumbSize = getimagesizefromstring($thumbBytes);
        $largeSize = getimagesizefromstring($largeBytes);

        $this->assertSame(100, $thumbSize[0]);
        $this->assertSame(100, $thumbSize[1]);
        $this->assertSame(IMAGETYPE_WEBP, $thumbSize[2]);

        $this->assertLessThanOrEqual(400, $largeSize[0]);
        $this->assertLessThanOrEqual(400, $largeSize[1]);
        $this->assertSame(IMAGETYPE_PNG, $largeSize[2]);

        $this->assertSame($file, $image->file());
        $this->assertSame($file, $thumb->file());
        $this->assertSame($file, $large->file());
    }

    public function test_two_variants_from_request_image()
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->image('avatar.jpg', 600, 600);

        $image = new Image(fn () => $file->getContent(), $file);

        $avatar = $image->orient()->cover(200, 200)->toWebp();
        $placeholder = $image->scale(40, 40)->blur(15)->toWebp()->quality(50);

        $avatar->store('avatars', 'local');
        $placeholder->store('placeholders', 'local');

        $avatarFiles = Storage::disk('local')->files('avatars');
        $placeholderFiles = Storage::disk('local')->files('placeholders');

        $this->assertCount(1, $avatarFiles);
        $this->assertCount(1, $placeholderFiles);

        $avatarSize = getimagesizefromstring(Storage::disk('local')->get($avatarFiles[0]));
        $placeholderSize = getimagesizefromstring(Storage::disk('local')->get($placeholderFiles[0]));

        $this->assertSame(200, $avatarSize[0]);
        $this->assertSame(200, $avatarSize[1]);

        $this->assertSame(40, $placeholderSize[0]);
        $this->assertSame(40, $placeholderSize[1]);

        $this->assertSame([600, 600], $image->dimensions());
        $this->assertSame('avatar.jpg', $image->file()->getClientOriginalName());
    }

    public function test_from_facade_creates_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 200, 200);

        $image = \Illuminate\Support\Facades\Image::from($file->getRealPath());

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([200, 200], $image->dimensions());
    }

    public function test_read_facade_creates_image()
    {
        $contents = $this->fakeImageContents(150, 150);

        $image = \Illuminate\Support\Facades\Image::read($contents);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([150, 150], $image->dimensions());
    }

    public function test_storage_image_creates_image()
    {
        Storage::fake('local');

        $contents = $this->fakeImageContents(300, 200);
        Storage::disk('local')->put('photos/test.jpg', $contents);

        $image = Storage::disk('local')->image('photos/test.jpg');

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([300, 200], $image->dimensions());
    }

    public function test_stringable_to_image()
    {
        $contents = $this->fakeImageContents(100, 100);

        $image = str($contents)->toImage();

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([100, 100], $image->dimensions());
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }
}
