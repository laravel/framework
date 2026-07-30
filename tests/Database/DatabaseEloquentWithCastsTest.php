<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\MissingAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Image\Image as ImageFacade;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase

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
            $table->string('image');
            $table->string('encoded');
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

    public function testImageCast()
    {
        $img = Image::create([
            'web_url' => 'https://example.com/favicon.ico',
            'storage_path' => 'avatars/john_doe.png',
            'image' => '',
            'encoded' => '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',
        ]);

        $this->assertInstanceOf(ImageFacade::class, $img->web_url);
        $this->assertInstanceOf(ImageFacade::class, $img->storage_path);
        $this->assertInstanceOf(ImageFacade::class, $img->image);
        $this->assertInstanceOf(ImageFacade::class, $img->encoded);
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
