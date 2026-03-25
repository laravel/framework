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

        $result = $image->toWebp()->toBytes();

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_to_webp_and_to_bytes()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toWebp()->toBytes();

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
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

        $thumb = $image->cover(100, 100)->toWebp();
        $large = $image->scale(200, 200)->toWebp();

        $thumbBytes = $thumb->toBytes();
        $largeBytes = $large->toBytes();

        $thumbSize = getimagesizefromstring($thumbBytes);
        $largeSize = getimagesizefromstring($largeBytes);

        $this->assertSame(100, $thumbSize[0]);
        $this->assertSame(100, $thumbSize[1]);
        $this->assertSame(IMAGETYPE_WEBP, $thumbSize[2]);

        $this->assertSame(200, $largeSize[0]);
        $this->assertSame(200, $largeSize[1]);
        $this->assertSame(IMAGETYPE_WEBP, $largeSize[2]);
    }

    public function test_store_saves_to_disk()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toWebp()->store('images', 'local');

        $files = Storage::disk('local')->files('images');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.webp', $files[0]);
    }

    public function test_store_as_saves_with_custom_name()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toWebp()->storeAs('images', 'avatar.webp', 'local');

        Storage::disk('local')->assertExists('images/avatar.webp');
    }

    public function test_mime_type_after_format_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $this->assertSame('image/webp', $image->toWebp()->mimeType());
    }

    public function test_extension_after_format_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $this->assertSame('webp', $image->toWebp()->extension());
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
        $large = $image->scale(400, 400)->toWebp();

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
        $this->assertSame(IMAGETYPE_WEBP, $largeSize[2]);

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

    public function test_from_path_facade_creates_image()
    {
        $file = UploadedFile::fake()->image('test.jpg', 200, 200);

        $image = \Illuminate\Support\Facades\Image::fromPath($file->getRealPath());

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([200, 200], $image->dimensions());
    }

    public function test_from_bytes_facade_creates_image()
    {
        $contents = $this->fakeImageContents(150, 150);

        $image = \Illuminate\Support\Facades\Image::fromBytes($contents);

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([150, 150], $image->dimensions());
    }

    public function test_from_base64_facade_creates_image()
    {
        $contents = $this->fakeImageContents(120, 120);

        $image = \Illuminate\Support\Facades\Image::fromBase64(base64_encode($contents));

        $this->assertInstanceOf(Image::class, $image);
        $this->assertSame([120, 120], $image->dimensions());
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

    public function test_sharpen_after_scale()
    {
        $image = new Image($this->fakeImageContents(400, 400));

        $result = $image->scale(200, 200)->sharpen(10)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(200, $width);
        $this->assertSame(200, $height);
    }

    public function test_flip_preserves_dimensions()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $result = $image->flip()->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_flop_preserves_dimensions()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $result = $image->flop()->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_flip_and_flop_together()
    {
        $image = new Image($this->fakeImageContents(200, 200));

        $result = $image->flip()->flop()->toBytes();

        $this->assertNotEmpty($result);
        $this->assertSame([200, 200], getimagesizefromstring($result) ? [getimagesizefromstring($result)[0], getimagesizefromstring($result)[1]] : [0, 0]);
    }

    public function test_all_operations_combined()
    {
        $image = new Image($this->fakeImageContents(800, 600));

        $result = $image
            ->orient()
            ->cover(200, 200)
            ->blur(5)
            ->greyscale()
            ->sharpen(10)
            ->flip()
            ->toWebp()
            ->quality(80);

        $bytes = $result->toBytes();
        $size = getimagesizefromstring($bytes);

        $this->assertSame(200, $size[0]);
        $this->assertSame(200, $size[1]);
        $this->assertSame(IMAGETYPE_WEBP, $size[2]);
    }

    public function test_to_bytes_is_idempotent()
    {
        $image = new Image($this->fakeImageContents(100, 100));
        $processed = $image->cover(50, 50)->toWebp();

        $first = $processed->toBytes();
        $second = $processed->toBytes();

        $this->assertSame($first, $second);
    }

    public function test_width_and_height_helpers()
    {
        $image = new Image($this->fakeImageContents(400, 300));
        $covered = $image->cover(200, 150);

        $this->assertSame(200, $covered->width());
        $this->assertSame(150, $covered->height());
    }

    public function test_to_base64_produces_valid_base64()
    {
        $image = new Image($this->fakeImageContents(100, 100));
        $result = $image->cover(50, 50)->toWebp();

        $base64 = $result->toBase64();

        $this->assertNotFalse(base64_decode($base64, true));
        $this->assertSame($result->toBytes(), base64_decode($base64));
    }

    public function test_to_data_uri_produces_valid_data_uri()
    {
        $image = new Image($this->fakeImageContents(100, 100));
        $result = $image->toWebp();

        $dataUri = $result->toDataUri();

        $this->assertStringStartsWith('data:image/webp;base64,', $dataUri);
    }

    public function test_store_with_string_disk_option()
    {
        Storage::fake('custom');

        $image = new Image($this->fakeImageContents(100, 100));
        $image->toWebp()->store('images', 'custom');

        $files = Storage::disk('custom')->files('images');

        $this->assertCount(1, $files);
    }

    public function test_store_with_array_disk_option()
    {
        Storage::fake('custom');

        $image = new Image($this->fakeImageContents(100, 100));
        $image->toWebp()->store('images', ['disk' => 'custom']);

        $files = Storage::disk('custom')->files('images');

        $this->assertCount(1, $files);
    }

    public function test_second_cover_overrides_first()
    {
        $image = new Image($this->fakeImageContents(400, 400));

        $result = $image->cover(200, 200)->cover(100, 100)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(100, $height);
    }

    public function test_store_as_with_name_only()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->storeAs('avatar.jpg', options: 'local');

        Storage::disk('local')->assertExists('avatar.jpg');
    }

    public function test_store_publicly_sets_visibility()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toWebp()->storePublicly('images', 'local');

        $files = Storage::disk('local')->files('images');

        $this->assertCount(1, $files);
    }

    public function test_store_publicly_as()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->toWebp()->storePubliclyAs('images', 'public-avatar.webp', 'local');

        Storage::disk('local')->assertExists('images/public-avatar.webp');
    }

    public function test_store_with_empty_path()
    {
        Storage::fake('local');

        $image = new Image($this->fakeImageContents(100, 100));

        $image->store('', 'local');

        $files = Storage::disk('local')->allFiles();

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.jpg', $files[0]);
    }

    public function test_hash_name_changes_extension_after_format_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $jpgName = $image->hashName();
        $webpName = $image->toWebp()->hashName();

        $this->assertStringEndsWith('.jpg', $jpgName);
        $this->assertStringEndsWith('.webp', $webpName);
    }

    public function test_to_jpg_conversion()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toJpg()->toBytes();

        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($result)[2]);
    }

    public function test_to_jpeg_alias_works()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->toJpeg()->toBytes();

        $this->assertSame(IMAGETYPE_JPEG, getimagesizefromstring($result)[2]);
    }

    public function test_optimize_shortcut_produces_webp()
    {
        $image = new Image($this->fakeImageContents(100, 100));

        $result = $image->optimize()->toBytes();

        $this->assertSame(IMAGETYPE_WEBP, getimagesizefromstring($result)[2]);
    }

    public function test_orient_does_not_change_dimensions_on_non_rotated_image()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $result = $image->orient();

        $this->assertSame(300, $result->width());
        $this->assertSame(200, $result->height());
    }

    public function test_greyscale_does_not_change_dimensions()
    {
        $image = new Image($this->fakeImageContents(200, 150));

        $result = $image->greyscale();

        $this->assertSame(200, $result->width());
        $this->assertSame(150, $result->height());
    }

    public function test_quality_alone_changes_file_size()
    {
        $image = new Image($this->fakeImageContents(200, 200));

        $default = $image->toBytes();
        $low = $image->quality(1)->toBytes();

        $this->assertNotSame(strlen($default), strlen($low));
    }

    public function test_request_image_returns_image_with_file()
    {
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);
        $image = new Image(fn () => $file->getContent(), $file);

        $this->assertNotNull($image->file());
        $this->assertSame('avatar.jpg', $image->file()->getClientOriginalName());
        $this->assertSame([100, 100], $image->dimensions());
    }

    public function test_format_conversion_does_not_change_dimensions()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $webp = $image->toWebp()->toBytes();
        $jpg = $image->toJpg()->toBytes();

        [$webpWidth, $webpHeight] = getimagesizefromstring($webp);
        [$jpgWidth, $jpgHeight] = getimagesizefromstring($jpg);

        $this->assertSame(300, $webpWidth);
        $this->assertSame(200, $webpHeight);
        $this->assertSame(300, $jpgWidth);
        $this->assertSame(200, $jpgHeight);
    }

    public function test_quality_does_not_change_dimensions()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $result = $image->quality(50)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_quality_and_format_does_not_change_dimensions()
    {
        $image = new Image($this->fakeImageContents(300, 200));

        $result = $image->quality(90)->toWebp()->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(300, $width);
        $this->assertSame(200, $height);
    }

    public function test_scale_down_does_not_upscale()
    {
        $image = new Image($this->fakeImageContents(100, 80));

        $result = $image->scale(800, 600)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(100, $width);
        $this->assertSame(80, $height);
    }

    public function test_scale_down_shrinks_larger_images()
    {
        $image = new Image($this->fakeImageContents(800, 600));

        $result = $image->scale(400, 400)->toBytes();

        [$width, $height] = getimagesizefromstring($result);

        $this->assertSame(400, $width);
        $this->assertSame(300, $height);
    }

    public function test_store_with_default_disk()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->toWebp()->store('avatars');

        $files = Storage::files('avatars');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('.webp', $files[0]);
    }

    public function test_store_with_no_arguments()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->store();

        $files = Storage::allFiles();

        $this->assertCount(1, $files);
    }

    public function test_store_as_with_path_and_name_only()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->storeAs('avatars', 'photo.jpg');

        Storage::assertExists('avatars/photo.jpg');
    }

    public function test_store_as_with_name_only_no_options()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->storeAs('photo.jpg');

        Storage::assertExists('photo.jpg');
    }

    public function test_store_publicly_with_default_disk()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->storePublicly('avatars');

        $files = Storage::files('avatars');

        $this->assertCount(1, $files);
    }

    public function test_store_publicly_as_with_name_only()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->storePubliclyAs('avatar.jpg');

        Storage::assertExists('avatar.jpg');
    }

    public function test_store_publicly_as_with_path_and_name()
    {
        Storage::fake();

        $image = new Image($this->fakeImageContents(100, 100));
        $image->storePubliclyAs('avatars', 'photo.jpg');

        Storage::assertExists('avatars/photo.jpg');
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }
}
