<?php

namespace Illuminate\Tests\Database;

use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        $container = Container::getInstance();
        $container->singleton(Generator::class, function ($app, $parameters) {
            return \Faker\Factory::create('en_US');
        });
        $container->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('App\\');

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('options')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('profiles', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->string('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('website')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->foreignId('commentable_id');
            $table->string('commentable_type');
            $table->foreignId('user_id');
            $table->string('body');
            $table->softDeletes();
            $table->timestamps();
        });

        $this->schema()->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('role_user', function ($table) {
            $table->foreignId('role_id');
            $table->foreignId('user_id');
            $table->string('admin')->default('N');
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        m::close();

        $this->schema()->drop('users');

        Container::setInstance(null);
    }

    public function test_the_factory_method_can_be_used_from_a_has_many_relation()
    {
        /** @var RelationFactoryTestUser $user */
        $user = RelationFactoryTestUserFactory::new()->create();

        $post = $user->posts()->factory()->create([
            'title' => 'test',
        ]);

        $this->assertInstanceOf(RelationFactoryTestPost::class, $post);
    }

    public function test_the_factory_method_can_be_used_from_a_has_one_relation()
    {
        /** @var RelationFactoryTestUser $user */
        $user = RelationFactoryTestUserFactory::new()->create();

        $profile = $user->profile()->factory()->create([
            'bio' => 'Software developer and tech enthusiast.',
            'avatar' => 'https://www.nyan.cat/cats/original.gif',
        ]);

        $this->assertInstanceOf(RelationFactoryTestProfile::class, $profile);
        $this->assertEquals('https://www.nyan.cat/cats/original.gif', $profile->avatar);
        $this->assertEquals('Software developer and tech enthusiast.', $profile->bio);
    }

    public function test_the_factory_method_can_be_used_from_a_polymorphic_relation()
    {
        /** @var RelationFactoryTestPost $post */
        $post = RelationFactoryTestPostFactory::new()->create();

        $comment = $post->comments()->factory()->create();

        $this->assertInstanceOf(RelationFactoryTestComment::class, $comment);
    }

    public function test_multiple_can_be_created_by_using_the_count_parameter_of_the_factory_method()
    {
        /** @var RelationFactoryTestUser $user */
        $user = RelationFactoryTestUserFactory::new()->create();

        $posts = $user->posts()->factory(2)->create();

        $this->assertInstanceOf(Collection::class, $posts);
        $this->assertCount(2, $posts);
        $this->assertInstanceOf(RelationFactoryTestPost::class, $posts->first());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
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

class RelationFactoryTestUserFactory extends Factory
{
    protected $model = RelationFactoryTestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'options' => null,
        ];
    }
}

class RelationFactoryTestUser extends Eloquent
{
    use HasFactory;

    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(RelationFactoryTestPost::class, 'user_id');
    }

    public function profile()
    {
        return $this->hasOne(RelationFactoryTestProfile::class);
    }

    public function roles()
    {
        return $this->belongsToMany(RelationFactoryTestRole::class, 'role_user', 'user_id', 'role_id')->withPivot('admin');
    }
}

class RelationFactoryTestPostFactory extends Factory
{
    protected $model = RelationFactoryTestPost::class;

    public function definition()
    {
        return [
            'user_id' => RelationFactoryTestUserFactory::new(),
            'title' => $this->faker->name(),
        ];
    }
}

class RelationFactoryTestProfileFactory extends Factory
{
    protected $model = RelationFactoryTestProfile::class;

    public function definition()
    {
        return [
            'user_id' => RelationFactoryTestUserFactory::new(),
            'bio' => $this->faker->text(),
            'website' => 'https://'.$this->faker->domainName(),
            'avatar' => $this->faker->imageUrl(),
        ];
    }
}

class RelationFactoryTestProfile extends Eloquent
{
    use HasFactory;

    protected $table = 'profiles';

    protected static function newFactory()
    {
        return new RelationFactoryTestProfileFactory();
    }

    public function user()
    {
        return $this->belongsTo(RelationFactoryTestUser::class, 'user_id');
    }
}

class RelationFactoryTestPost extends Eloquent
{
    use SoftDeletes, HasFactory;

    protected $table = 'posts';

    protected static function newFactory()
    {
        return new RelationFactoryTestPostFactory();
    }

    public function user()
    {
        return $this->belongsTo(RelationFactoryTestUser::class, 'user_id');
    }

    public function author()
    {
        return $this->belongsTo(RelationFactoryTestUser::class, 'user_id');
    }

    public function comments()
    {
        return $this->morphMany(RelationFactoryTestComment::class, 'commentable');
    }
}

class RelationFactoryTestCommentFactory extends Factory
{
    protected $model = RelationFactoryTestComment::class;

    public function definition()
    {
        return [
            'commentable_id' => RelationFactoryTestPostFactory::new(),
            'commentable_type' => RelationFactoryTestPost::class,
            'user_id' => fn () => RelationFactoryTestUserFactory::new(),
            'body' => $this->faker->name(),
        ];
    }

    public function trashed()
    {
        return $this->state([
            'deleted_at' => Carbon::now()->subWeek(),
        ]);
    }
}

class RelationFactoryTestComment extends Eloquent
{
    use SoftDeletes, HasFactory;

    protected $table = 'comments';

    protected static function newFactory()
    {
        return new RelationFactoryTestCommentFactory();
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}

class RelationFactoryTestRoleFactory extends Factory
{
    protected $model = FactoryTestRole::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}

class RelationFactoryTestRole extends Eloquent
{
    use HasFactory;

    protected $table = 'roles';

    protected $touches = ['users'];

    protected static function newFactory()
    {
        return new RelationFactoryTestRoleFactory();
    }

    public function users()
    {
        return $this->belongsToMany(FactoryTestUser::class, 'role_user', 'role_id', 'user_id')->withPivot('admin');
    }
}
