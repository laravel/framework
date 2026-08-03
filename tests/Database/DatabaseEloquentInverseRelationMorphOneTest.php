<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentInverseRelationMorphOneTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    protected function setUp(): void
    {
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
        $this->schema()->create('test_posts', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema()->create('test_images', function ($table) {
            $table->increments('id');
            $table->morphs('imageable');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('test_posts');
        $this->schema()->drop('test_images');
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphOneInverseImageModel::factory(6)->create();
        $posts = MorphOneInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('image'));
            $image = $post->image;
            $this->assertTrue($image->relationLoaded('imageable'));
            $this->assertSame($post, $image->imageable);
        }
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphOneInverseImageModel::factory(6)->create();
        $posts = MorphOneInversePostModel::with('image')->get();

        foreach ($posts as $post) {
            $image = $post->getRelation('image');

            $this->assertTrue($image->relationLoaded('imageable'));
            $this->assertSame($post, $image->imageable);
        }
    }

    public function testMorphOneGuessedInverseRelationIsProperlySetToParentWhenLazyLoaded()
    {
        MorphOneInverseImageModel::factory(6)->create();
        $posts = MorphOneInversePostModel::all();

        foreach ($posts as $post) {
            $this->assertFalse($post->relationLoaded('guessedImage'));
            $image = $post->guessedImage;
            $this->assertTrue($image->relationLoaded('imageable'));
            $this->assertSame($post, $image->imageable);
        }
    }

    public function testMorphOneGuessedInverseRelationIsProperlySetToParentWhenEagerLoaded()
    {
        MorphOneInverseImageModel::factory(6)->create();
        $posts = MorphOneInversePostModel::with('guessedImage')->get();

        foreach ($posts as $post) {
            $image = $post->getRelation('guessedImage');

            $this->assertTrue($image->relationLoaded('imageable'));
            $this->assertSame($post, $image->imageable);
        }
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenMaking()
    {
        $post = MorphOneInversePostModel::create();

        $image = $post->image()->make();

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenCreating()
    {
        $post = MorphOneInversePostModel::create();

        $image = $post->image()->create();

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenCreatingQuietly()
    {
        $post = MorphOneInversePostModel::create();

        $image = $post->image()->createQuietly();

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenForceCreating()
    {
        $post = MorphOneInversePostModel::create();

        $image = $post->image()->forceCreate();

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenSaving()
    {
        $post = MorphOneInversePostModel::create();
        $image = MorphOneInverseImageModel::make();

        $this->assertFalse($image->relationLoaded('imageable'));
        $post->image()->save($image);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenSavingQuietly()
    {
        $post = MorphOneInversePostModel::create();
        $image = MorphOneInverseImageModel::make();

        $this->assertFalse($image->relationLoaded('imageable'));
        $post->image()->saveQuietly($image);

        $this->assertTrue($image->relationLoaded('imageable'));
        $this->assertSame($post, $image->imageable);
    }

    public function testMorphOneInverseRelationIsProperlySetToParentWhenUpdating()
    {
        $post = MorphOneInversePostModel::create();
        $image = MorphOneInverseImageModel::factory()->create();

        $this->assertTrue($post->isNot($image->imageable));

        $post->image()->save($image);

        $this->assertTrue($post->is($image->imageable));
        $this->assertSame($post, $image->imageable);
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

class MorphOneInversePostModel extends Model
{
    use HasFactory;

    protected $table = 'test_posts';
    protected $fillable = ['id'];

    protected static function newFactory()
    {
        return new MorphOneInversePostModelFactory();
    }

    public function image(): MorphOne
    {
        return $this->morphOne(MorphOneInverseImageModel::class, 'imageable')->inverse('imageable');
    }

    public function guessedImage(): MorphOne
    {
        return $this->morphOne(MorphOneInverseImageModel::class, 'imageable')->inverse();
    }
}

class MorphOneInversePostModelFactory extends Factory
{
    protected $model = MorphOneInversePostModel::class;

    public function definition()
    {
        return [];
    }
}

class MorphOneInverseImageModel extends Model
{
    use HasFactory;

    protected $table = 'test_images';
    protected $fillable = ['id', 'imageable_type', 'imageable_id'];

    protected static function newFactory()
    {
        return new MorphOneInverseImageModelFactory();
    }

    public function imageable(): MorphTo
    {
        return $this->morphTo('imageable');
    }
}

class MorphOneInverseImageModelFactory extends Factory
{
    protected $model = MorphOneInverseImageModel::class;

    public function definition()
    {
        return [
            'imageable_type' => MorphOneInversePostModel::class,
            'imageable_id' => MorphOneInversePostModel::factory(),
        ];
    }
}
