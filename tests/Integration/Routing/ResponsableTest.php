<?php

use Faker\Generator;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Contracts\Support\Responsable;

/**
 * @group integration
 */
class ResponsableTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $factory = new Factory($app->make(Generator::class));

        $factory->define(TestResponsableApiResponseUser::class, function (Generator $faker) {
            return [
                'first_name' => $faker->name,
                'last_name' => $faker->name,
            ];
        });

        $factory->define(TestResponsableApiResponsePost::class, function (Generator $faker) {
            return [
                'title' => $faker->name,
            ];
        });

        $app->extend(Factory::class, function ($app) use ($factory) {
            return $factory;
        });
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('first_name');
            $table->string('last_name');
        });

        Schema::create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('user_id');
        });
    }

    public function test_responsable_objects_are_rendered()
    {
        Route::get('/responsable', function () {
            return new TestResponsableResponse;
        });

        $response = $this->get('/responsable');

        $this->assertEquals(201, $response->status());
        $this->assertEquals('Taylor', $response->headers->get('X-Test-Header'));
        $this->assertEquals('hello world', $response->getContent());
    }

    public function test_responsable_api_objects()
    {
        $users = factory(TestResponsableApiResponseUser::class, 5)->create();

        Route::get('/responsable', function () use ($users) {
            return new TestResponsableApiResponse($users->first());
        });

        Route::get('/responsableCollection', function () use ($users) {
            return new TestResponsableApiResponse($users);
        });

        Route::get('/responsablePaginator', function () use ($users) {
            return new TestResponsableApiResponse(TestResponsableApiResponseUser::paginate(2));
        });

        $singleResourceREsponse = json_decode($this->get('/responsable')->getContent(), true);

        $this->assertEquals([
            'data' => [
                'name' => $users->first()->first_name.' '.$users->first()->last_name,
                'posts' => [],
            ],
        ], $singleResourceREsponse);

        $collectionResponse = json_decode($this->get('/responsableCollection')->getContent(), true);

        $this->assertArrayHasKey('data', $collectionResponse);
        $this->assertCount(5, $collectionResponse['data']);
        $this->assertEquals([
            'name' => $users->first()->first_name.' '.$users->first()->last_name,
            'posts' => [],
        ], $collectionResponse['data'][0]);

        $paginatorResponse = json_decode($this->get('/responsablePaginator')->getContent(), true);

        $this->assertArrayHasKey('data', $paginatorResponse);
        $this->assertArrayHasKey('meta', $paginatorResponse);
        $this->assertCount(2, $paginatorResponse['data']);
        $this->assertEquals([
            'name' => $users->first()->first_name.' '.$users->first()->last_name,
            'posts' => [],
        ], $collectionResponse['data'][0]);
        $this->assertArrayHasKey('per_page', $paginatorResponse['meta']);
        $this->assertArrayHasKey('current_page', $paginatorResponse['meta']);
        $this->assertArrayHasKey('next_page_url', $paginatorResponse['meta']);
    }

    public function test_responsable_api_objects_with_status()
    {
        $users = factory(TestResponsableApiResponseUser::class, 5)->create();

        Route::get('/responsable', function () use ($users) {
            return (new TestResponsableApiResponse($users->first()))->withStatus(201);
        });

        $this->assertEquals(201, $this->get('/responsable')->status());
    }

    public function test_responsable_api_objects_with_meta()
    {
        $users = factory(TestResponsableApiResponseUser::class, 5)->create();

        Route::get('/responsable', function () use ($users) {
            return (new TestResponsableApiResponse($users->first()))->withMeta([
                'foo' => 'bar',
            ]);
        });

        $response = json_decode($this->get('/responsable')->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertEquals(['foo' => 'bar'], $response['meta']);
    }

    public function test_responsable_api_objects_with_related()
    {
        $users = factory(TestResponsableApiResponseUser::class, 5)->create()->each(function ($user) {
            $user->posts()->createMany(
                factory(TestResponsableApiResponsePost::class, 2)->make()->toArray()
            );
        });

        Route::get('/responsable', function () use ($users) {
            return TestResponsableApiResponse::with($users->first());
        });

        $response = json_decode($this->get('/responsable')->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertCount(2, $response['data']['posts']['data']);
    }
}

class TestResponsableResponse implements Responsable
{
    public function toResponse()
    {
        return response('hello world', 201, ['X-Test-Header' => 'Taylor']);
    }
}

class TestResponsableApiResponse implements Responsable
{
    use \Illuminate\Http\ApiResponseTrait;

    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function transformResource($resource)
    {
        return [
            'name' => $resource->first_name.' '.$resource->last_name,
            'posts' => TestResponsableApiResponseForPosts::with($resource->posts)->transform(),
        ];
    }
}

class TestResponsableApiResponseForPosts implements Responsable
{
    use \Illuminate\Http\ApiResponseTrait;

    private $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function transformResource($resource)
    {
        return [
            'title' => $resource->title,
        ];
    }
}

class TestResponsableApiResponseUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function posts()
    {
        return $this->hasMany(TestResponsableApiResponsePost::class, 'user_id');
    }
}

class TestResponsableApiResponsePost extends Model
{
    public $table = 'posts';
    public $timestamps = false;
    protected $guarded = ['id'];
}
