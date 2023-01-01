<?php

namespace Illuminate\Tests\Database;

use BadMethodCallException;
use Carbon\Carbon;
use Faker\Generator;
use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\CrossJoinSequence;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Tests\Database\Fixtures\Models\Money\Price;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class DatabaseEloquentFactoryTest extends TestCase
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

    public function test_basic_model_can_be_created()
    {
        $user = FactoryTestUserFactory::new()->create();
        $this->assertInstanceOf(Eloquent::class, $user);

        $user = FactoryTestUserFactory::new()->createOne();
        $this->assertInstanceOf(Eloquent::class, $user);

        $user = FactoryTestUserFactory::new()->create(['name' => 'Taylor Otwell']);
        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertSame('Taylor Otwell', $user->name);

        $user = FactoryTestUserFactory::new()->set('name', 'Taylor Otwell')->create();
        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertSame('Taylor Otwell', $user->name);

        $users = FactoryTestUserFactory::new()->createMany([
            ['name' => 'Taylor Otwell'],
            ['name' => 'Jeffrey Way'],
        ]);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(2, $users);

        $users = FactoryTestUserFactory::times(10)->create();
        $this->assertCount(10, $users);
    }

    public function test_expanded_closure_attributes_are_resolved_and_passed_to_closures()
    {
        $user = FactoryTestUserFactory::new()->create([
            'name' => function () {
                return 'taylor';
            },
            'options' => function ($attributes) {
                return $attributes['name'].'-options';
            },
        ]);

        $this->assertSame('taylor-options', $user->options);
    }

    public function test_expanded_closure_attribute_returning_a_factory_is_resolved()
    {
        $post = FactoryTestPostFactory::new()->create([
            'title' => 'post',
            'user_id' => fn ($attributes) => FactoryTestUserFactory::new([
                'options' => $attributes['title'].'-options',
            ]),
        ]);

        $this->assertEquals('post-options', $post->user->options);
    }

    public function test_make_creates_unpersisted_model_instance()
    {
        $user = FactoryTestUserFactory::new()->makeOne();
        $this->assertInstanceOf(Eloquent::class, $user);

        $user = FactoryTestUserFactory::new()->make(['name' => 'Taylor Otwell']);

        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertSame('Taylor Otwell', $user->name);
        $this->assertCount(0, FactoryTestUser::all());
    }

    public function test_basic_model_attributes_can_be_created()
    {
        $user = FactoryTestUserFactory::new()->raw();
        $this->assertIsArray($user);

        $user = FactoryTestUserFactory::new()->raw(['name' => 'Taylor Otwell']);
        $this->assertIsArray($user);
        $this->assertSame('Taylor Otwell', $user['name']);
    }

    public function test_expanded_model_attributes_can_be_created()
    {
        $post = FactoryTestPostFactory::new()->raw();
        $this->assertIsArray($post);

        $post = FactoryTestPostFactory::new()->raw(['title' => 'Test Title']);
        $this->assertIsArray($post);
        $this->assertIsInt($post['user_id']);
        $this->assertSame('Test Title', $post['title']);
    }

    public function test_lazy_model_attributes_can_be_created()
    {
        $userFunction = FactoryTestUserFactory::new()->lazy();
        $this->assertIsCallable($userFunction);
        $this->assertInstanceOf(Eloquent::class, $userFunction());

        $userFunction = FactoryTestUserFactory::new()->lazy(['name' => 'Taylor Otwell']);
        $this->assertIsCallable($userFunction);

        $user = $userFunction();
        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertSame('Taylor Otwell', $user->name);
    }

    public function test_multiple_model_attributes_can_be_created()
    {
        $posts = FactoryTestPostFactory::new()->times(10)->raw();
        $this->assertIsArray($posts);

        $this->assertCount(10, $posts);
    }

    public function test_after_creating_and_making_callbacks_are_called()
    {
        $user = FactoryTestUserFactory::new()
            ->afterMaking(function ($user) {
                $_SERVER['__test.user.making'] = $user;
            })
            ->afterCreating(function ($user) {
                $_SERVER['__test.user.creating'] = $user;
            })
            ->create();

        $this->assertSame($user, $_SERVER['__test.user.making']);
        $this->assertSame($user, $_SERVER['__test.user.creating']);

        unset($_SERVER['__test.user.making'], $_SERVER['__test.user.creating']);
    }

    public function test_has_many_relationship()
    {
        $users = FactoryTestUserFactory::times(10)
            ->has(
                FactoryTestPostFactory::times(3)
                    ->state(function ($attributes, $user) {
                        // Test parent is passed to child state mutations...
                        $_SERVER['__test.post.state-user'] = $user;

                        return [];
                    })
                    // Test parents passed to callback...
                    ->afterCreating(function ($post, $user) {
                        $_SERVER['__test.post.creating-post'] = $post;
                        $_SERVER['__test.post.creating-user'] = $user;
                    }),
                'posts'
            )
            ->create();

        $this->assertCount(10, FactoryTestUser::all());
        $this->assertCount(30, FactoryTestPost::all());
        $this->assertCount(3, FactoryTestUser::latest()->first()->posts);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.post.creating-post']);
        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.post.creating-user']);
        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.post.state-user']);

        unset($_SERVER['__test.post.creating-post'], $_SERVER['__test.post.creating-user'], $_SERVER['__test.post.state-user']);
    }

    public function test_belongs_to_relationship()
    {
        $posts = FactoryTestPostFactory::times(3)
            ->for(FactoryTestUserFactory::new(['name' => 'Taylor Otwell']), 'user')
            ->create();

        $this->assertCount(3, $posts->filter(function ($post) {
            return $post->user->name === 'Taylor Otwell';
        }));

        $this->assertCount(1, FactoryTestUser::all());
        $this->assertCount(3, FactoryTestPost::all());
    }

    public function test_belongs_to_relationship_with_existing_model_instance()
    {
        $user = FactoryTestUserFactory::new(['name' => 'Taylor Otwell'])->create();
        $posts = FactoryTestPostFactory::times(3)
            ->for($user, 'user')
            ->create();

        $this->assertCount(3, $posts->filter(function ($post) use ($user) {
            return $post->user->is($user);
        }));

        $this->assertCount(1, FactoryTestUser::all());
        $this->assertCount(3, FactoryTestPost::all());
    }

    public function test_belongs_to_relationship_with_existing_model_instance_with_relationship_name_implied_from_model()
    {
        $user = FactoryTestUserFactory::new(['name' => 'Taylor Otwell'])->create();
        $posts = FactoryTestPostFactory::times(3)
            ->for($user)
            ->create();

        $this->assertCount(3, $posts->filter(function ($post) use ($user) {
            return $post->factoryTestUser->is($user);
        }));

        $this->assertCount(1, FactoryTestUser::all());
        $this->assertCount(3, FactoryTestPost::all());
    }

    public function test_morph_to_relationship()
    {
        $posts = FactoryTestCommentFactory::times(3)
            ->for(FactoryTestPostFactory::new(['title' => 'Test Title']), 'commentable')
            ->create();

        $this->assertSame('Test Title', FactoryTestPost::first()->title);
        $this->assertCount(3, FactoryTestPost::first()->comments);

        $this->assertCount(1, FactoryTestPost::all());
        $this->assertCount(3, FactoryTestComment::all());
    }

    public function test_morph_to_relationship_with_existing_model_instance()
    {
        $post = FactoryTestPostFactory::new(['title' => 'Test Title'])->create();
        $posts = FactoryTestCommentFactory::times(3)
            ->for($post, 'commentable')
            ->create();

        $this->assertSame('Test Title', FactoryTestPost::first()->title);
        $this->assertCount(3, FactoryTestPost::first()->comments);

        $this->assertCount(1, FactoryTestPost::all());
        $this->assertCount(3, FactoryTestComment::all());
    }

    public function test_belongs_to_many_relationship()
    {
        $users = FactoryTestUserFactory::times(3)
            ->hasAttached(
                FactoryTestRoleFactory::times(3)->afterCreating(function ($role, $user) {
                    $_SERVER['__test.role.creating-role'] = $role;
                    $_SERVER['__test.role.creating-user'] = $user;
                }),
                ['admin' => 'Y'],
                'roles'
            )
            ->create();

        $this->assertCount(9, FactoryTestRole::all());

        $user = FactoryTestUser::latest()->first();

        $this->assertCount(3, $user->roles);
        $this->assertSame('Y', $user->roles->first()->pivot->admin);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.role.creating-role']);
        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.role.creating-user']);

        unset($_SERVER['__test.role.creating-role'], $_SERVER['__test.role.creating-user']);
    }

    public function test_belongs_to_many_relationship_related_models_set_on_instance_when_touching_owner()
    {
        $user = FactoryTestUserFactory::new()->create();
        $role = FactoryTestRoleFactory::new()->hasAttached($user, [], 'users')->create();

        $this->assertCount(1, $role->users);
    }

    public function test_relation_can_be_loaded_before_model_is_created()
    {
        $user = FactoryTestUserFactory::new(['name' => 'Taylor Otwell'])->createOne();

        $post = FactoryTestPostFactory::new()
            ->for($user, 'user')
            ->afterMaking(function (FactoryTestPost $post) {
                $post->load('user');
            })
            ->createOne();

        $this->assertTrue($post->relationLoaded('user'));
        $this->assertTrue($post->user->is($user));

        $this->assertCount(1, FactoryTestUser::all());
        $this->assertCount(1, FactoryTestPost::all());
    }

    public function test_belongs_to_many_relationship_with_existing_model_instances()
    {
        $roles = FactoryTestRoleFactory::times(3)
            ->afterCreating(function ($role) {
                $_SERVER['__test.role.creating-role'] = $role;
            })
            ->create();
        FactoryTestUserFactory::times(3)
            ->hasAttached($roles, ['admin' => 'Y'], 'roles')
            ->create();

        $this->assertCount(3, FactoryTestRole::all());

        $user = FactoryTestUser::latest()->first();

        $this->assertCount(3, $user->roles);
        $this->assertSame('Y', $user->roles->first()->pivot->admin);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.role.creating-role']);

        unset($_SERVER['__test.role.creating-role']);
    }

    public function test_belongs_to_many_relationship_with_existing_model_instances_using_array()
    {
        $roles = FactoryTestRoleFactory::times(3)
            ->afterCreating(function ($role) {
                $_SERVER['__test.role.creating-role'] = $role;
            })
            ->create();
        FactoryTestUserFactory::times(3)
            ->hasAttached($roles->toArray(), ['admin' => 'Y'], 'roles')
            ->create();

        $this->assertCount(3, FactoryTestRole::all());

        $user = FactoryTestUser::latest()->first();

        $this->assertCount(3, $user->roles);
        $this->assertSame('Y', $user->roles->first()->pivot->admin);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.role.creating-role']);

        unset($_SERVER['__test.role.creating-role']);
    }

    public function test_belongs_to_many_relationship_with_existing_model_instances_with_relationship_name_implied_from_model()
    {
        $roles = FactoryTestRoleFactory::times(3)
            ->afterCreating(function ($role) {
                $_SERVER['__test.role.creating-role'] = $role;
            })
            ->create();
        FactoryTestUserFactory::times(3)
            ->hasAttached($roles, ['admin' => 'Y'])
            ->create();

        $this->assertCount(3, FactoryTestRole::all());

        $user = FactoryTestUser::latest()->first();

        $this->assertCount(3, $user->factoryTestRoles);
        $this->assertSame('Y', $user->factoryTestRoles->first()->pivot->admin);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.role.creating-role']);

        unset($_SERVER['__test.role.creating-role']);
    }

    public function test_sequences()
    {
        $users = FactoryTestUserFactory::times(2)->sequence(
            ['name' => 'Taylor Otwell'],
            ['name' => 'Abigail Otwell'],
        )->create();

        $this->assertSame('Taylor Otwell', $users[0]->name);
        $this->assertSame('Abigail Otwell', $users[1]->name);

        $user = FactoryTestUserFactory::new()
            ->hasAttached(
                FactoryTestRoleFactory::times(4),
                new Sequence(['admin' => 'Y'], ['admin' => 'N']),
                'roles'
            )
            ->create();

        $this->assertCount(4, $user->roles);

        $this->assertCount(2, $user->roles->filter(function ($role) {
            return $role->pivot->admin === 'Y';
        }));

        $this->assertCount(2, $user->roles->filter(function ($role) {
            return $role->pivot->admin === 'N';
        }));

        $users = FactoryTestUserFactory::times(2)->sequence(function ($sequence) {
            return ['name' => 'index: '.$sequence->index];
        })->create();

        $this->assertSame('index: 0', $users[0]->name);
        $this->assertSame('index: 1', $users[1]->name);
    }

    public function test_counted_sequence()
    {
        $factory = FactoryTestUserFactory::new()->forEachSequence(
            ['name' => 'Taylor Otwell'],
            ['name' => 'Abigail Otwell'],
            ['name' => 'Dayle Rees']
        );

        $class = new ReflectionClass($factory);
        $prop = $class->getProperty('count');
        $prop->setAccessible(true);
        $value = $prop->getValue($factory);

        $this->assertSame(3, $value);
    }

    public function test_cross_join_sequences()
    {
        $assert = function ($users) {
            $assertions = [
                ['first_name' => 'Thomas', 'last_name' => 'Anderson'],
                ['first_name' => 'Thomas', 'last_name' => 'Smith'],
                ['first_name' => 'Agent', 'last_name' => 'Anderson'],
                ['first_name' => 'Agent', 'last_name' => 'Smith'],
            ];

            foreach ($assertions as $key => $assertion) {
                $this->assertSame(
                    $assertion,
                    $users[$key]->only('first_name', 'last_name'),
                );
            }
        };

        $usersByClass = FactoryTestUserFactory::times(4)
            ->state(
                new CrossJoinSequence(
                    [['first_name' => 'Thomas'], ['first_name' => 'Agent']],
                    [['last_name' => 'Anderson'], ['last_name' => 'Smith']],
                ),
            )
            ->make();

        $assert($usersByClass);

        $usersByMethod = FactoryTestUserFactory::times(4)
            ->crossJoinSequence(
                [['first_name' => 'Thomas'], ['first_name' => 'Agent']],
                [['last_name' => 'Anderson'], ['last_name' => 'Smith']],
            )
            ->make();

        $assert($usersByMethod);
    }

    public function test_resolve_nested_model_factories()
    {
        Factory::useNamespace('Factories\\');

        $resolves = [
            'App\\Foo' => 'Factories\\FooFactory',
            'App\\Models\\Foo' => 'Factories\\FooFactory',
            'App\\Models\\Nested\\Foo' => 'Factories\\Nested\\FooFactory',
            'App\\Models\\Really\\Nested\\Foo' => 'Factories\\Really\\Nested\\FooFactory',
        ];

        foreach ($resolves as $model => $factory) {
            $this->assertEquals($factory, Factory::resolveFactoryName($model));
        }
    }

    public function test_resolve_nested_model_name_from_factory()
    {
        Container::getInstance()->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('Illuminate\\Tests\\Database\\Fixtures\\');

        Factory::useNamespace('Illuminate\\Tests\\Database\\Fixtures\\Factories\\');

        $factory = Price::factory();

        $this->assertSame(Price::class, $factory->modelName());
    }

    public function test_resolve_non_app_nested_model_factories()
    {
        Container::getInstance()->instance(Application::class, $app = m::mock(Application::class));
        $app->shouldReceive('getNamespace')->andReturn('Foo\\');

        Factory::useNamespace('Factories\\');

        $resolves = [
            'Foo\\Bar' => 'Factories\\BarFactory',
            'Foo\\Models\\Bar' => 'Factories\\BarFactory',
            'Foo\\Models\\Nested\\Bar' => 'Factories\\Nested\\BarFactory',
            'Foo\\Models\\Really\\Nested\\Bar' => 'Factories\\Really\\Nested\\BarFactory',
        ];

        foreach ($resolves as $model => $factory) {
            $this->assertEquals($factory, Factory::resolveFactoryName($model));
        }
    }

    public function test_model_has_factory()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $this->assertInstanceOf(FactoryTestUserFactory::class, FactoryTestUser::factory());
    }

    public function test_dynamic_has_and_for_methods()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $user = FactoryTestUserFactory::new()->hasPosts(3)->create();

        $this->assertCount(3, $user->posts);

        $post = FactoryTestPostFactory::new()
            ->forAuthor(['name' => 'Taylor Otwell'])
            ->hasComments(2)
            ->create();

        $this->assertInstanceOf(FactoryTestUser::class, $post->author);
        $this->assertSame('Taylor Otwell', $post->author->name);
        $this->assertCount(2, $post->comments);
    }

    public function test_can_be_macroable()
    {
        $factory = FactoryTestUserFactory::new();
        $factory->macro('getFoo', function () {
            return 'Hello World';
        });

        $this->assertSame('Hello World', $factory->getFoo());
    }

    public function test_factory_can_conditionally_execute_code()
    {
        FactoryTestUserFactory::new()
            ->when(true, function () {
                $this->assertTrue(true);
            })
            ->when(false, function () {
                $this->fail('Unreachable code that has somehow been reached.');
            })
            ->unless(false, function () {
                $this->assertTrue(true);
            })
            ->unless(true, function () {
                $this->fail('Unreachable code that has somehow been reached.');
            });
    }

    public function test_dynamic_trashed_state_for_softdeletes_models()
    {
        $now = Carbon::create(2020, 6, 7, 8, 9);
        Carbon::setTestNow($now);
        $post = FactoryTestPostFactory::new()->trashed()->create();

        $this->assertTrue($post->deleted_at->equalTo($now->subDay()));

        $deleted_at = Carbon::create(2020, 1, 2, 3, 4, 5);
        $post = FactoryTestPostFactory::new()->trashed($deleted_at)->create();

        $this->assertTrue($deleted_at->equalTo($post->deleted_at));

        Carbon::setTestNow();
    }

    public function test_dynamic_trashed_state_respects_existing_state()
    {
        $now = Carbon::create(2020, 6, 7, 8, 9);
        Carbon::setTestNow($now);
        $comment = FactoryTestCommentFactory::new()->trashed()->create();

        $this->assertTrue($comment->deleted_at->equalTo($now->subWeek()));

        Carbon::setTestNow();
    }

    public function test_dynamic_trashed_state_throws_exception_when_not_a_softdeletes_model()
    {
        $this->expectException(BadMethodCallException::class);
        FactoryTestUserFactory::new()->trashed()->create();
    }

    public function test_model_instances_can_be_used_in_place_of_nested_factories()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $user = FactoryTestUserFactory::new()->create();
        $post = FactoryTestPostFactory::new()
            ->recycle($user)
            ->hasComments(2)
            ->create();

        $this->assertSame(1, FactoryTestUser::count());
        $this->assertEquals($user->id, $post->user_id);
        $this->assertEquals($user->id, $post->comments[0]->user_id);
        $this->assertEquals($user->id, $post->comments[1]->user_id);
    }

    public function test_for_method_recycles_models()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $user = FactoryTestUserFactory::new()->create();
        $post = FactoryTestPostFactory::new()
            ->recycle($user)
            ->for(FactoryTestUserFactory::new())
            ->create();

        $this->assertSame(1, FactoryTestUser::count());
    }

    public function test_has_method_does_not_reassign_the_parent()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $post = FactoryTestPostFactory::new()->create();
        $user = FactoryTestUserFactory::new()
            ->recycle($post)
            // The recycled post already belongs to a user, so it shouldn't be recycled here.
            ->has(FactoryTestPostFactory::new(), 'posts')
            ->create();

        $this->assertSame(2, FactoryTestPost::count());
    }

    public function test_multiple_models_can_be_provided_to_recycle()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $users = FactoryTestUserFactory::new()->count(3)->create();

        $posts = FactoryTestPostFactory::new()
            ->recycle($users)
            ->for(FactoryTestUserFactory::new())
            ->has(FactoryTestCommentFactory::new()->count(5), 'comments')
            ->count(2)
            ->create();

        $this->assertSame(3, FactoryTestUser::count());
    }

    public function test_recycled_models_can_be_combined_with_multiple_calls()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $users = FactoryTestUserFactory::new()
            ->count(2)
            ->create();
        $posts = FactoryTestPostFactory::new()
            ->recycle($users)
            ->count(2)
            ->create();
        $additionalUser = FactoryTestUserFactory::new()
            ->create();
        $additionalPost = FactoryTestPostFactory::new()
            ->recycle($additionalUser)
            ->create();

        $this->assertSame(3, FactoryTestUser::count());
        $this->assertSame(3, FactoryTestPost::count());

        $comments = FactoryTestCommentFactory::new()
            ->recycle($users)
            ->recycle($posts)
            ->recycle([$additionalUser, $additionalPost])
            ->count(5)
            ->create();

        $this->assertSame(3, FactoryTestUser::count());
        $this->assertSame(3, FactoryTestPost::count());
    }

    public function test_no_models_can_be_provided_to_recycle()
    {
        Factory::guessFactoryNamesUsing(function ($model) {
            return $model.'Factory';
        });

        $posts = FactoryTestPostFactory::new()
            ->recycle([])
            ->count(2)
            ->create();

        $this->assertSame(2, FactoryTestPost::count());
        $this->assertSame(2, FactoryTestUser::count());
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

class FactoryTestUserFactory extends Factory
{
    protected $model = FactoryTestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'options' => null,
        ];
    }
}

class FactoryTestUser extends Eloquent
{
    use HasFactory;

    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(FactoryTestPost::class, 'user_id');
    }

    public function roles()
    {
        return $this->belongsToMany(FactoryTestRole::class, 'role_user', 'user_id', 'role_id')->withPivot('admin');
    }

    public function factoryTestRoles()
    {
        return $this->belongsToMany(FactoryTestRole::class, 'role_user', 'user_id', 'role_id')->withPivot('admin');
    }
}

class FactoryTestPostFactory extends Factory
{
    protected $model = FactoryTestPost::class;

    public function definition()
    {
        return [
            'user_id' => FactoryTestUserFactory::new(),
            'title' => $this->faker->name,
        ];
    }
}

class FactoryTestPost extends Eloquent
{
    use SoftDeletes;

    protected $table = 'posts';

    public function user()
    {
        return $this->belongsTo(FactoryTestUser::class, 'user_id');
    }

    public function factoryTestUser()
    {
        return $this->belongsTo(FactoryTestUser::class, 'user_id');
    }

    public function author()
    {
        return $this->belongsTo(FactoryTestUser::class, 'user_id');
    }

    public function comments()
    {
        return $this->morphMany(FactoryTestComment::class, 'commentable');
    }
}

class FactoryTestCommentFactory extends Factory
{
    protected $model = FactoryTestComment::class;

    public function definition()
    {
        return [
            'commentable_id' => FactoryTestPostFactory::new(),
            'commentable_type' => FactoryTestPost::class,
            'user_id' => FactoryTestUserFactory::new(),
            'body' => $this->faker->name,
        ];
    }

    public function trashed()
    {
        return $this->state([
            'deleted_at' => Carbon::now()->subWeek(),
        ]);
    }
}

class FactoryTestComment extends Eloquent
{
    use SoftDeletes;

    protected $table = 'comments';

    public function commentable()
    {
        return $this->morphTo();
    }
}

class FactoryTestRoleFactory extends Factory
{
    protected $model = FactoryTestRole::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}

class FactoryTestRole extends Eloquent
{
    protected $table = 'roles';

    protected $touches = ['users'];

    public function users()
    {
        return $this->belongsToMany(FactoryTestUser::class, 'role_user', 'role_id', 'user_id')->withPivot('admin');
    }
}
