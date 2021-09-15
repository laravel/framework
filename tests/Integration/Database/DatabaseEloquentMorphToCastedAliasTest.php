<?php

namespace Illuminate\Tests\Integration\Database {

    use Illuminate\Database\Eloquent\Relations\Relation;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;
    use MorphTo\Test\TestAttachment;
    use MorphTo\Test\TestImage;

    /**
     * @group integration
     */
    class DatabaseEloquentMorphToCastedAliasTest extends DatabaseTestCase
    {
        private $oldMap;

        protected function setUp(): void
        {
            parent::setUp();
            $this->oldMap = Relation::morphMap();
            Relation::morphMap([
                'image' => \MorphTo\Test\TestImage::class,
            ]);
            Schema::create('test_images', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->timestamps();
            });

            Schema::create('test_attachments', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('content_id');
                $table->string('content_type');
                $table->timestamps();
            });
        }

        protected function tearDown(): void
        {
            parent::tearDown();
            Relation::morphMap($this->oldMap, false);
        }

        public function testCastedAlias()
        {
            $image = TestImage::create(['title' => 'Hello world']);
            TestImage::create(['title' => 'Hello world2']);
            TestAttachment::create(['content_type' => 'image', 'content_id' => $image->id]);

            $fetchedUser = TestAttachment::query()->whereHas('content')->first();
            self::assertEquals('Hello world', $fetchedUser->content->title);
        }
    }
}

namespace MorphTo\Test {

    use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
    use Illuminate\Database\Eloquent\Model;

    class TestCastedValue
    {
        private $value;

        public function __construct($value)
        {
            $this->value = $value;
        }

        public function __toString()
        {
            return (string) $this->value;
        }
    }

    class CastToObject implements CastsAttributes
    {
        public function get($model, string $key, $value, array $attributes)
        {
            return new TestCastedValue($value);
        }

        public function set($model, string $key, $value, array $attributes)
        {
            return (string) $value;
        }
    }

    class TestImage extends Model
    {
        protected $guarded = [];
    }

    class TestAttachment extends Model
    {
        protected $guarded = [];

        protected $casts = [
            'content_type' => \MorphTo\Test\CastToObject::class,
        ];

        public function content()
        {
            return $this->morphTo();
        }
    }
}
