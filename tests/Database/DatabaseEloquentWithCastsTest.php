<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Image\Image as ImageFacade;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithCastsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema()->create('times', function ($table) {
            $table->increments('id');
            $table->time('time');
            $table->timestamps();
        });

        $this->schema()->create('unique_times', function ($table) {
            $table->increments('id');
            $table->time('time')->unique();
            $table->timestamps();
        });

        $this->schema()->create('images', function ($table) {
            $table->increments('id');
            $table->string('storage_path');
            $table->string('web_url');
            $table->text('image');
            $table->text('encoded');
            $table->timestamps();
        });
    }

    public function testWithFirstOrNew()
    {
        $time1 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrNew(['time' => '07:30']);

        Time::query()->insert(['time' => '07:30']);

        $time2 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrNew(['time' => '07:30']);

        $this->assertSame('07:30', $time1->time);
        $this->assertSame($time1->time, $time2->time);
    }

    public function testWithFirstOrCreate()
    {
        $time1 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrCreate(['time' => '07:30']);

        $time2 = Time::query()->withCasts(['time' => 'string'])
            ->firstOrCreate(['time' => '07:30']);

        $this->assertSame($time1->id, $time2->id);
    }

    public function testWithCreateOrFirst()
    {
        $time1 = UniqueTime::query()->withCasts(['time' => 'string'])
            ->createOrFirst(['time' => '07:30']);

        $time2 = UniqueTime::query()->withCasts(['time' => 'string'])
            ->createOrFirst(['time' => '07:30']);

        $this->assertSame($time1->id, $time2->id);
    }

    public function testThrowsExceptionIfCastableAttributeWasNotRetrievedAndPreventMissingAttributesIsEnabled()
    {
        Time::create(['time' => Carbon::now()]);
        $originalMode = Model::preventsAccessingMissingAttributes();
        Model::preventAccessingMissingAttributes();

        $this->expectException(MissingAttributeException::class);
        try {
            $time = Time::query()->select('id')->first();
            $this->assertNull($time->time);
        } finally {
            Model::preventAccessingMissingAttributes($originalMode);
        }
    }

    protected function makeImage(): Image
    {
        return new Image($this->fakeImageContents());
    }

    protected function fakeImageContents(int $width = 100, int $height = 100): string
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);

        return file_get_contents($file->getRealPath());
    }

    public function testImageCast()
    {
        $file = UploadedFile::fake()->image('test.jpg', $width, $height);
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

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

class Time extends Eloquent
{
    protected $guarded = [];

    protected $casts = [
        'time' => 'datetime',
    ];
}

class UniqueTime extends Eloquent
{
    protected $guarded = [];

    protected $casts = [
        'time' => 'datetime',
    ];
}

class Image extends Eloquent
{
    protected $guarded = [];

    protected $casts = [
        'web_url' => 'image:url',
        'storage_path' => 'image:storage',
        'image' => 'image:bytes',
        'encoded' => 'image:base64',
    ];
}
