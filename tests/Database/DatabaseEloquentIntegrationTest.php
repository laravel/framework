<?php

use Illuminate\Database\Connection;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\AbstractPaginator as Paginator;

class DatabaseEloquentIntegrationTest extends PHPUnit_Framework_TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ], 'second_connection');

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema('default')->create('test_orders', function ($table) {
            $table->increments('id');
            $table->string('item_type');
            $table->integer('item_id');
            $table->timestamps();
        });

        $this->schema('second_connection')->create('test_items', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        foreach (['default', 'second_connection'] as $connection) {
            $this->schema($connection)->create('users', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('email');
                $table->timestamps();
            });

            $this->schema($connection)->create('friends', function ($table) {
                $table->integer('user_id');
                $table->integer('friend_id');
            });

            $this->schema($connection)->create('posts', function ($table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('parent_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });

            $this->schema($connection)->create('photos', function ($table) {
                $table->increments('id');
                $table->morphs('imageable');
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        foreach (['default', 'second_connection'] as $connection) {
            $this->schema($connection)->drop('users');
            $this->schema($connection)->drop('friends');
            $this->schema($connection)->drop('posts');
            $this->schema($connection)->drop('photos');
        }

        Relation::morphMap([], false);
    }

    /**
     * Tests...
     */
    public function testBasicModelRetrieval()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $model = EloquentTestUser::where('email', 'taylorotwell@gmail.com')->first();
        $this->assertEquals('taylorotwell@gmail.com', $model->email);
        $this->assertTrue(isset($model->email));
        $this->assertTrue(isset($model->friends));

        $model = EloquentTestUser::find(1);
        $this->assertInstanceOf('EloquentTestUser', $model);
        $this->assertEquals(1, $model->id);

        $model = EloquentTestUser::find(2);
        $this->assertInstanceOf('EloquentTestUser', $model);
        $this->assertEquals(2, $model->id);

        $missing = EloquentTestUser::find(3);
        $this->assertNull($missing);

        $collection = EloquentTestUser::find([]);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $collection);
        $this->assertEquals(0, $collection->count());

        $collection = EloquentTestUser::find([1, 2, 3]);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $collection);
        $this->assertEquals(2, $collection->count());

        $models = EloquentTestUser::where('id', 1)->cursor();
        foreach ($models as $model) {
            $this->assertEquals(1, $model->id);
        }

        $records = DB::table('users')->where('id', 1)->cursor();
        foreach ($records as $record) {
            $this->assertEquals(1, $record->id);
        }

        $records = DB::cursor('select * from users where id = ?', [1]);
        foreach ($records as $record) {
            $this->assertEquals(1, $record->id);
        }
    }

    public function testBasicModelCollectionRetrieval()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $models = EloquentTestUser::oldest('id')->get();

        $this->assertEquals(2, $models->count());
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $models);
        $this->assertInstanceOf('EloquentTestUser', $models[0]);
        $this->assertInstanceOf('EloquentTestUser', $models[1]);
        $this->assertEquals('taylorotwell@gmail.com', $models[0]->email);
        $this->assertEquals('abigailotwell@gmail.com', $models[1]->email);
    }

    public function testPaginatedModelCollectionRetrieval()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);
        EloquentTestUser::create(['id' => 3, 'email' => 'foo@gmail.com']);

        Paginator::currentPageResolver(function () {
            return 1;
        });
        $models = EloquentTestUser::oldest('id')->paginate(2);

        $this->assertEquals(2, $models->count());
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $models);
        $this->assertInstanceOf('EloquentTestUser', $models[0]);
        $this->assertInstanceOf('EloquentTestUser', $models[1]);
        $this->assertEquals('taylorotwell@gmail.com', $models[0]->email);
        $this->assertEquals('abigailotwell@gmail.com', $models[1]->email);

        Paginator::currentPageResolver(function () {
            return 2;
        });
        $models = EloquentTestUser::oldest('id')->paginate(2);

        $this->assertEquals(1, $models->count());
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $models);
        $this->assertInstanceOf('EloquentTestUser', $models[0]);
        $this->assertEquals('foo@gmail.com', $models[0]->email);
    }

    public function testPaginatedModelCollectionRetrievalWhenNoElements()
    {
        Paginator::currentPageResolver(function () {
            return 1;
        });
        $models = EloquentTestUser::oldest('id')->paginate(2);

        $this->assertEquals(0, $models->count());
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $models);

        Paginator::currentPageResolver(function () {
            return 2;
        });
        $models = EloquentTestUser::oldest('id')->paginate(2);

        $this->assertEquals(0, $models->count());
    }

    public function testPaginatedModelCollectionRetrievalWhenNoElementsAndDefaultPerPage()
    {
        $models = EloquentTestUser::oldest('id')->paginate();

        $this->assertEquals(0, $models->count());
        $this->assertInstanceOf('Illuminate\Pagination\LengthAwarePaginator', $models);
    }

    public function testCountForPaginationWithGrouping()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);
        EloquentTestUser::create(['id' => 3, 'email' => 'foo@gmail.com']);
        EloquentTestUser::create(['id' => 4, 'email' => 'foo@gmail.com']);

        $query = EloquentTestUser::groupBy('email')->getQuery();

        $this->assertEquals(3, $query->getCountForPagination());
    }

    public function testFirstOrCreate()
    {
        $user1 = EloquentTestUser::firstOrCreate(['email' => 'taylorotwell@gmail.com']);

        $this->assertEquals('taylorotwell@gmail.com', $user1->email);
        $this->assertNull($user1->name);

        $user2 = EloquentTestUser::firstOrCreate(
            ['email' => 'taylorotwell@gmail.com'],
            ['name' => 'Taylor Otwell']
        );

        $this->assertEquals($user1->id, $user2->id);
        $this->assertEquals('taylorotwell@gmail.com', $user2->email);
        $this->assertNull($user2->name);

        $user3 = EloquentTestUser::firstOrCreate(
            ['email' => 'abigailotwell@gmail.com'],
            ['name' => 'Abigail Otwell']
        );

        $this->assertNotEquals($user3->id, $user1->id);
        $this->assertEquals('abigailotwell@gmail.com', $user3->email);
        $this->assertEquals('Abigail Otwell', $user3->name);
    }

    public function testPluck()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $simple = EloquentTestUser::oldest('id')->pluck('users.email')->all();
        $keyed = EloquentTestUser::oldest('id')->pluck('users.email', 'users.id')->all();

        $this->assertEquals(['taylorotwell@gmail.com', 'abigailotwell@gmail.com'], $simple);
        $this->assertEquals([1 => 'taylorotwell@gmail.com', 2 => 'abigailotwell@gmail.com'], $keyed);
    }

    public function testPluckWithJoin()
    {
        $user1 = EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        $user2 = EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $user2->posts()->create(['id' => 1, 'name' => 'First post']);
        $user1->posts()->create(['id' => 2, 'name' => 'Second post']);

        $query = EloquentTestUser::join('posts', 'users.id', '=', 'posts.user_id');

        $this->assertEquals([1 => 'First post', 2 => 'Second post'], $query->pluck('posts.name', 'posts.id')->all());
        $this->assertEquals([2 => 'First post', 1 => 'Second post'], $query->pluck('posts.name', 'users.id')->all());
        $this->assertEquals(['abigailotwell@gmail.com' => 'First post', 'taylorotwell@gmail.com' => 'Second post'], $query->pluck('posts.name', 'users.email as user_email')->all());
    }

    public function testFindOrFail()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $single = EloquentTestUser::findOrFail(1);
        $multiple = EloquentTestUser::findOrFail([1, 2]);

        $this->assertInstanceOf('EloquentTestUser', $single);
        $this->assertEquals('taylorotwell@gmail.com', $single->email);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $multiple);
        $this->assertInstanceOf('EloquentTestUser', $multiple[0]);
        $this->assertInstanceOf('EloquentTestUser', $multiple[1]);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFailWithSingleIdThrowsModelNotFoundException()
    {
        EloquentTestUser::findOrFail(1);
    }

    /**
     * @expectedException Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function testFindOrFailWithMultipleIdsThrowsModelNotFoundException()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::findOrFail([1, 2]);
    }

    public function testOneToOneRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->post()->create(['name' => 'First Post']);

        $post = $user->post;
        $user = $post->user;

        $this->assertTrue(isset($user->post->name));
        $this->assertInstanceOf('EloquentTestUser', $user);
        $this->assertInstanceOf('EloquentTestPost', $post);
        $this->assertEquals('taylorotwell@gmail.com', $user->email);
        $this->assertEquals('First Post', $post->name);
    }

    public function testIssetLoadsInRelationshipIfItIsntLoadedAlready()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->post()->create(['name' => 'First Post']);

        $this->assertTrue(isset($user->post->name));
    }

    public function testOneToManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->posts()->create(['name' => 'First Post']);
        $user->posts()->create(['name' => 'Second Post']);

        $posts = $user->posts;
        $post2 = $user->posts()->where('name', 'Second Post')->first();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $posts);
        $this->assertEquals(2, $posts->count());
        $this->assertInstanceOf('EloquentTestPost', $posts[0]);
        $this->assertInstanceOf('EloquentTestPost', $posts[1]);
        $this->assertInstanceOf('EloquentTestPost', $post2);
        $this->assertEquals('Second Post', $post2->name);
        $this->assertInstanceOf('EloquentTestUser', $post2->user);
        $this->assertEquals('taylorotwell@gmail.com', $post2->user->email);
    }

    public function testBasicModelHydration()
    {
        $user = new EloquentTestUser(['email' => 'taylorotwell@gmail.com']);
        $user->setConnection('second_connection');
        $user->save();

        $user = new EloquentTestUser(['email' => 'abigailotwell@gmail.com']);
        $user->setConnection('second_connection');
        $user->save();

        $models = EloquentTestUser::hydrateRaw('SELECT * FROM users WHERE email = ?', ['abigailotwell@gmail.com'], 'second_connection');

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $models);
        $this->assertInstanceOf('EloquentTestUser', $models[0]);
        $this->assertEquals('abigailotwell@gmail.com', $models[0]->email);
        $this->assertEquals('second_connection', $models[0]->getConnectionName());
        $this->assertEquals(1, $models->count());
    }

    public function testHasOnSelfReferencingBelongsToManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);

        $this->assertTrue(isset($user->friends[0]->id));

        $results = EloquentTestUser::has('friends')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testWhereHasOnSelfReferencingBelongsToManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);

        $results = EloquentTestUser::whereHas('friends', function ($query) {
            $query->where('email', 'abigailotwell@gmail.com');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testHasOnNestedSelfReferencingBelongsToManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);
        $nestedFriend = $friend->friends()->create(['email' => 'foo@gmail.com']);

        $results = EloquentTestUser::has('friends.friends')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testWhereHasOnNestedSelfReferencingBelongsToManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);
        $nestedFriend = $friend->friends()->create(['email' => 'foo@gmail.com']);

        $results = EloquentTestUser::whereHas('friends.friends', function ($query) {
            $query->where('email', 'foo@gmail.com');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testHasOnSelfReferencingBelongsToManyRelationshipWithWherePivot()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);

        $results = EloquentTestUser::has('friendsOne')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testHasOnNestedSelfReferencingBelongsToManyRelationshipWithWherePivot()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);
        $nestedFriend = $friend->friends()->create(['email' => 'foo@gmail.com']);

        $results = EloquentTestUser::has('friendsOne.friendsTwo')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('taylorotwell@gmail.com', $results->first()->email);
    }

    public function testHasOnSelfReferencingBelongsToRelationship()
    {
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'user_id' => 1]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 2]);

        $results = EloquentTestPost::has('parentPost')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Child Post', $results->first()->name);
    }

    public function testWhereHasOnSelfReferencingBelongsToRelationship()
    {
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'user_id' => 1]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 2]);

        $results = EloquentTestPost::whereHas('parentPost', function ($query) {
            $query->where('name', 'Parent Post');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Child Post', $results->first()->name);
    }

    public function testHasOnNestedSelfReferencingBelongsToRelationship()
    {
        $grandParentPost = EloquentTestPost::create(['name' => 'Grandparent Post', 'user_id' => 1]);
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'parent_id' => $grandParentPost->id, 'user_id' => 2]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 3]);

        $results = EloquentTestPost::has('parentPost.parentPost')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Child Post', $results->first()->name);
    }

    public function testWhereHasOnNestedSelfReferencingBelongsToRelationship()
    {
        $grandParentPost = EloquentTestPost::create(['name' => 'Grandparent Post', 'user_id' => 1]);
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'parent_id' => $grandParentPost->id, 'user_id' => 2]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 3]);

        $results = EloquentTestPost::whereHas('parentPost.parentPost', function ($query) {
            $query->where('name', 'Grandparent Post');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Child Post', $results->first()->name);
    }

    public function testHasOnSelfReferencingHasManyRelationship()
    {
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'user_id' => 1]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 2]);

        $results = EloquentTestPost::has('childPosts')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Parent Post', $results->first()->name);
    }

    public function testWhereHasOnSelfReferencingHasManyRelationship()
    {
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'user_id' => 1]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 2]);

        $results = EloquentTestPost::whereHas('childPosts', function ($query) {
            $query->where('name', 'Child Post');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Parent Post', $results->first()->name);
    }

    public function testHasOnNestedSelfReferencingHasManyRelationship()
    {
        $grandParentPost = EloquentTestPost::create(['name' => 'Grandparent Post', 'user_id' => 1]);
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'parent_id' => $grandParentPost->id, 'user_id' => 2]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 3]);

        $results = EloquentTestPost::has('childPosts.childPosts')->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Grandparent Post', $results->first()->name);
    }

    public function testWhereHasOnNestedSelfReferencingHasManyRelationship()
    {
        $grandParentPost = EloquentTestPost::create(['name' => 'Grandparent Post', 'user_id' => 1]);
        $parentPost = EloquentTestPost::create(['name' => 'Parent Post', 'parent_id' => $grandParentPost->id, 'user_id' => 2]);
        $childPost = EloquentTestPost::create(['name' => 'Child Post', 'parent_id' => $parentPost->id, 'user_id' => 3]);

        $results = EloquentTestPost::whereHas('childPosts.childPosts', function ($query) {
            $query->where('name', 'Child Post');
        })->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('Grandparent Post', $results->first()->name);
    }

    public function testBelongsToManyRelationshipModelsAreProperlyHydratedOverChunkedRequest()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $friend = $user->friends()->create(['email' => 'abigailotwell@gmail.com']);

        EloquentTestUser::first()->friends()->chunk(2, function ($friends) use ($user, $friend) {
            $this->assertEquals(1, count($friends));
            $this->assertEquals('abigailotwell@gmail.com', $friends->first()->email);
            $this->assertEquals($user->id, $friends->first()->pivot->user_id);
            $this->assertEquals($friend->id, $friends->first()->pivot->friend_id);
        });
    }

    public function testBasicHasManyEagerLoading()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->posts()->create(['name' => 'First Post']);
        $user = EloquentTestUser::with('posts')->where('email', 'taylorotwell@gmail.com')->first();

        $this->assertEquals('First Post', $user->posts->first()->name);

        $post = EloquentTestPost::with('user')->where('name', 'First Post')->get();
        $this->assertEquals('taylorotwell@gmail.com', $post->first()->user->email);
    }

    public function testBasicNestedSelfReferencingHasManyEagerLoading()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $post = $user->posts()->create(['name' => 'First Post']);
        $post->childPosts()->create(['name' => 'Child Post', 'user_id' => $user->id]);

        $user = EloquentTestUser::with('posts.childPosts')->where('email', 'taylorotwell@gmail.com')->first();

        $this->assertNotNull($user->posts->first());
        $this->assertEquals('First Post', $user->posts->first()->name);

        $this->assertNotNull($user->posts->first()->childPosts->first());
        $this->assertEquals('Child Post', $user->posts->first()->childPosts->first()->name);

        $post = EloquentTestPost::with('parentPost.user')->where('name', 'Child Post')->get();
        $this->assertNotNull($post->first()->parentPost);
        $this->assertNotNull($post->first()->parentPost->user);
        $this->assertEquals('taylorotwell@gmail.com', $post->first()->parentPost->user->email);
    }

    public function testBasicMorphManyRelationship()
    {
        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->photos()->create(['name' => 'Avatar 1']);
        $user->photos()->create(['name' => 'Avatar 2']);
        $post = $user->posts()->create(['name' => 'First Post']);
        $post->photos()->create(['name' => 'Hero 1']);
        $post->photos()->create(['name' => 'Hero 2']);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->photos);
        $this->assertInstanceOf('EloquentTestPhoto', $user->photos[0]);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $post->photos);
        $this->assertInstanceOf('EloquentTestPhoto', $post->photos[0]);
        $this->assertEquals(2, $user->photos->count());
        $this->assertEquals(2, $post->photos->count());
        $this->assertEquals('Avatar 1', $user->photos[0]->name);
        $this->assertEquals('Avatar 2', $user->photos[1]->name);
        $this->assertEquals('Hero 1', $post->photos[0]->name);
        $this->assertEquals('Hero 2', $post->photos[1]->name);

        $photos = EloquentTestPhoto::orderBy('name')->get();

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $photos);
        $this->assertEquals(4, $photos->count());
        $this->assertInstanceOf('EloquentTestUser', $photos[0]->imageable);
        $this->assertInstanceOf('EloquentTestPost', $photos[2]->imageable);
        $this->assertEquals('taylorotwell@gmail.com', $photos[1]->imageable->email);
        $this->assertEquals('First Post', $photos[3]->imageable->name);
    }

    public function testMorphMapIsUsedForCreatingAndFetchingThroughRelation()
    {
        Relation::morphMap([
            'user' => 'EloquentTestUser',
            'post' => 'EloquentTestPost',
        ]);

        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->photos()->create(['name' => 'Avatar 1']);
        $user->photos()->create(['name' => 'Avatar 2']);
        $post = $user->posts()->create(['name' => 'First Post']);
        $post->photos()->create(['name' => 'Hero 1']);
        $post->photos()->create(['name' => 'Hero 2']);

        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $user->photos);
        $this->assertInstanceOf('EloquentTestPhoto', $user->photos[0]);
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Collection', $post->photos);
        $this->assertInstanceOf('EloquentTestPhoto', $post->photos[0]);
        $this->assertEquals(2, $user->photos->count());
        $this->assertEquals(2, $post->photos->count());
        $this->assertEquals('Avatar 1', $user->photos[0]->name);
        $this->assertEquals('Avatar 2', $user->photos[1]->name);
        $this->assertEquals('Hero 1', $post->photos[0]->name);
        $this->assertEquals('Hero 2', $post->photos[1]->name);

        $this->assertEquals('user', $user->photos[0]->imageable_type);
        $this->assertEquals('user', $user->photos[1]->imageable_type);
        $this->assertEquals('post', $post->photos[0]->imageable_type);
        $this->assertEquals('post', $post->photos[1]->imageable_type);
    }

    public function testMorphMapIsUsedWhenFetchingParent()
    {
        Relation::morphMap([
            'user' => 'EloquentTestUser',
            'post' => 'EloquentTestPost',
        ]);

        $user = EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);
        $user->photos()->create(['name' => 'Avatar 1']);

        $photo = EloquentTestPhoto::first();
        $this->assertEquals('user', $photo->imageable_type);
        $this->assertInstanceOf('EloquentTestUser', $photo->imageable);
    }

    public function testMorphMapIsMergedByDefault()
    {
        $map1 = [
            'user' => 'EloquentTestUser',
        ];
        $map2 = [
            'post' => 'EloquentTestPost',
        ];

        Relation::morphMap($map1);
        Relation::morphMap($map2);

        $this->assertEquals(array_merge($map1, $map2), Relation::morphMap());
    }

    public function testMorphMapOverwritesCurrentMap()
    {
        $map1 = [
            'user' => 'EloquentTestUser',
        ];
        $map2 = [
            'post' => 'EloquentTestPost',
        ];

        Relation::morphMap($map1, false);
        $this->assertEquals($map1, Relation::morphMap());
        Relation::morphMap($map2, false);
        $this->assertEquals($map2, Relation::morphMap());
    }

    public function testEmptyMorphToRelationship()
    {
        $photo = new EloquentTestPhoto;

        $this->assertNull($photo->imageable);
    }

    public function testSaveOrFail()
    {
        $date = '1970-01-01';
        $post = new EloquentTestPost([
            'user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date,
        ]);

        $this->assertTrue($post->saveOrFail());
        $this->assertEquals(1, EloquentTestPost::count());
    }

    /**
     * @expectedException Exception
     */
    public function testSaveOrFailWithDuplicatedEntry()
    {
        $date = '1970-01-01';
        EloquentTestPost::create([
            'id' => 1, 'user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date,
        ]);

        $post = new EloquentTestPost([
            'id' => 1, 'user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date,
        ]);

        $post->saveOrFail();
    }

    public function testMultiInsertsWithDifferentValues()
    {
        $date = '1970-01-01';
        $result = EloquentTestPost::insert([
            ['user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date],
            ['user_id' => 2, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date],
        ]);

        $this->assertTrue($result);
        $this->assertEquals(2, EloquentTestPost::count());
    }

    public function testMultiInsertsWithSameValues()
    {
        $date = '1970-01-01';
        $result = EloquentTestPost::insert([
            ['user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date],
            ['user_id' => 1, 'name' => 'Post', 'created_at' => $date, 'updated_at' => $date],
        ]);

        $this->assertTrue($result);
        $this->assertEquals(2, EloquentTestPost::count());
    }

    public function testNestedTransactions()
    {
        $user = EloquentTestUser::create(['email' => 'taylor@laravel.com']);
        $this->connection()->transaction(function () use ($user) {
            try {
                $this->connection()->transaction(function () use ($user) {
                    $user->email = 'otwell@laravel.com';
                    $user->save();
                    throw new Exception;
                });
            } catch (Exception $e) {
                // ignore the exception
            }
            $user = EloquentTestUser::first();
            $this->assertEquals('taylor@laravel.com', $user->email);
        });
    }

    public function testNestedTransactionsUsingSaveOrFailWillSucceed()
    {
        $user = EloquentTestUser::create(['email' => 'taylor@laravel.com']);
        $this->connection()->transaction(function () use ($user) {
            try {
                $user->email = 'otwell@laravel.com';
                $user->saveOrFail();
            } catch (Exception $e) {
                // ignore the exception
            }

            $user = EloquentTestUser::first();
            $this->assertEquals('otwell@laravel.com', $user->email);
            $this->assertEquals(1, $user->id);
        });
    }

    public function testNestedTransactionsUsingSaveOrFailWillFails()
    {
        $user = EloquentTestUser::create(['email' => 'taylor@laravel.com']);
        $this->connection()->transaction(function () use ($user) {
            try {
                $user->id = 'invalid';
                $user->email = 'otwell@laravel.com';
                $user->saveOrFail();
            } catch (Exception $e) {
                // ignore the exception
            }

            $user = EloquentTestUser::first();
            $this->assertEquals('taylor@laravel.com', $user->email);
            $this->assertEquals(1, $user->id);
        });
    }

    public function testToArrayIncludesDefaultFormattedTimestamps()
    {
        $model = new EloquentTestUser;

        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => '2012-12-05',
        ]);

        $array = $model->toArray();

        $this->assertEquals('2012-12-04 00:00:00', $array['created_at']);
        $this->assertEquals('2012-12-05 00:00:00', $array['updated_at']);
    }

    public function testToArrayIncludesCustomFormattedTimestamps()
    {
        $model = new EloquentTestUser;
        $model->setDateFormat('d-m-y');

        $model->setRawAttributes([
            'created_at' => '2012-12-04',
            'updated_at' => '2012-12-05',
        ]);

        $array = $model->toArray();

        $this->assertEquals('04-12-12', $array['created_at']);
        $this->assertEquals('05-12-12', $array['updated_at']);
    }

    public function testIncrementingPrimaryKeysAreCastToIntegersByDefault()
    {
        EloquentTestUser::create(['email' => 'taylorotwell@gmail.com']);

        $user = EloquentTestUser::first();
        $this->assertInternalType('int', $user->id);
    }

    public function testDefaultIncrementingPrimaryKeyIntegerCastCanBeOverwritten()
    {
        EloquentTestUserWithStringCastId::create(['email' => 'taylorotwell@gmail.com']);

        $user = EloquentTestUserWithStringCastId::first();
        $this->assertInternalType('string', $user->id);
    }

    public function testRelationsArePreloadedInGlobalScope()
    {
        $user = EloquentTestUserWithGlobalScope::create(['email' => 'taylorotwell@gmail.com']);
        $user->posts()->create(['name' => 'My Post']);

        $result = EloquentTestUserWithGlobalScope::first();

        $this->assertCount(1, $result->getRelations());
    }

    public function testForPageAfterIdCorrectlyPaginates()
    {
        EloquentTestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);
        EloquentTestUser::create(['id' => 2, 'email' => 'abigailotwell@gmail.com']);

        $results = EloquentTestUser::forPageAfterId(15, 1);
        $this->assertEquals(1, count($results));

        $results = EloquentTestUser::orderBy('id', 'desc')->forPageAfterId(15, 1);
        $this->assertEquals(1, count($results));
    }

    public function testMorphToRelationsAcrossDatabaseConnections()
    {
        $item = null;

        EloquentTestItem::create(['id' => 1]);
        EloquentTestOrder::create(['id' => 1, 'item_type' => EloquentTestItem::class, 'item_id' => 1]);
        try {
            $item = EloquentTestOrder::first()->item;
        } catch (Exception $e) {
            // ignore the exception
        }

        $this->assertInstanceOf('EloquentTestItem', $item);
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class EloquentTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function friends()
    {
        return $this->belongsToMany('EloquentTestUser', 'friends', 'user_id', 'friend_id');
    }

    public function friendsOne()
    {
        return $this->belongsToMany('EloquentTestUser', 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 1);
    }

    public function friendsTwo()
    {
        return $this->belongsToMany('EloquentTestUser', 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 2);
    }

    public function posts()
    {
        return $this->hasMany('EloquentTestPost', 'user_id');
    }

    public function post()
    {
        return $this->hasOne('EloquentTestPost', 'user_id');
    }

    public function photos()
    {
        return $this->morphMany('EloquentTestPhoto', 'imageable');
    }
}

class EloquentTestUserWithGlobalScope extends EloquentTestUser
{
    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($builder) {
            $builder->with('posts');
        });
    }
}

class EloquentTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('EloquentTestUser', 'user_id');
    }

    public function photos()
    {
        return $this->morphMany('EloquentTestPhoto', 'imageable');
    }

    public function childPosts()
    {
        return $this->hasMany('EloquentTestPost', 'parent_id');
    }

    public function parentPost()
    {
        return $this->belongsTo('EloquentTestPost', 'parent_id');
    }
}

class EloquentTestPhoto extends Eloquent
{
    protected $table = 'photos';
    protected $guarded = [];

    public function imageable()
    {
        return $this->morphTo();
    }
}

class EloquentTestUserWithStringCastId extends EloquentTestUser
{
    protected $casts = [
        'id' => 'string',
    ];
}

class EloquentTestOrder extends Eloquent
{
    protected $guarded = [];
    protected $table = 'test_orders';
    protected $with = ['item'];

    public function item()
    {
        return $this->morphTo();
    }
}

class EloquentTestItem extends Eloquent
{
    protected $guarded = [];
    protected $table = 'test_items';
    protected $connection = 'second_connection';
}
