<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelDateCastingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Image\Image as ImageFacade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelImageCastingTest extends DatabaseTestCase
{
    /** {@inheritdoc} */
    protected function afterRefreshingDatabase()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->string('storage_path');
            $table->string('web_url');
            $table->binary('image');
            $table->text('encoded');
            $table->timestamps();
        });
    }

    public function testImageCast()
    {
        $file = UploadedFile::fake()->image('test.jpg', 100, 100);
        $image = new ImageFacade(file_get_contents($file->getRealPath()));
        $base64 = $image->toBase64();
        $image->store('avatars/john_doe.png');
        $img = Image::create([
            'web_url' => 'https://example.com/favicon.ico',
            'storage_path' => 'avatars/john_doe.png',
            'image' => $image->toBytes(),
            'encoded' => $image->toBase64(),
        ]);

        $this->assertInstanceOf(ImageFacade::class, $img->web_url);
        $this->assertInstanceOf(ImageFacade::class, $img->storage_path);
        $this->assertInstanceOf(ImageFacade::class, $img->image);
        $this->assertInstanceOf(ImageFacade::class, $img->encoded);
        $this->assertSame($base64, $img->storage_path->toBase64());
        $this->assertSame($base64, $img->image->toBase64());
        $this->assertSame($base64, $img->encoded->toBase64());
    }
}

class Image extends Model
{
    protected $guarded = [];

    protected $casts = [
        'web_url' => 'image:url',
        'storage_path' => 'image:storage',
        'image' => 'image:bytes',
        'encoded' => 'image:base64',
    ];
}
