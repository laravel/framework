<?php

namespace Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentBelongsToManyTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('users_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('user_uuid');
            $table->string('post_uuid');
            $table->tinyInteger('is_draft')->default(1);
            $table->timestamps();
        });

        Schema::create('posts_tags', function (Blueprint $table) {
            $table->integer('post_id');
            $table->integer('tag_id')->default(0);
            $table->string('tag_name')->default('')->nullable();
            $table->string('flag')->default('')->nullable();
            $table->timestamps();
        });
    }

    public function testBasicCreateAndRetrieve()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([
            $tag->id => ['flag' => 'taylor'],
            $tag2->id => ['flag' => ''],
            $tag3->id => ['flag' => 'exclude'],
        ]);

        // Tags with flag = exclude should be excluded
        $this->assertCount(2, $post->tags);
        $this->assertInstanceOf(Collection::class, $post->tags);
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertEquals($tag2->name, $post->tags[1]->name);

        // Testing on the pivot model
        $this->assertInstanceOf(Pivot::class, $post->tags[0]->pivot);
        $this->assertEquals($post->id, $post->tags[0]->pivot->post_id);
        $this->assertSame('post_id', $post->tags[0]->pivot->getForeignKey());
        $this->assertSame('tag_id', $post->tags[0]->pivot->getOtherKey());
        $this->assertSame('posts_tags', $post->tags[0]->pivot->getTable());
        $this->assertEquals(
            [
                'post_id' => '1', 'tag_id' => '1', 'flag' => 'taylor',
                'created_at' => '2017-10-10T10:10:10.000000Z', 'updated_at' => '2017-10-10T10:10:10.000000Z',
            ],
            $post->tags[0]->pivot->toArray()
        );
    }

    public function testRefreshOnOtherModelWorks()
    {
        $post = Post::create(['title' => Str::random()]);
        $tag = Tag::create(['name' => $tagName = Str::random()]);

        $post->tags()->sync([
            $tag->id,
        ]);

        $post->load('tags');

        $loadedTag = $post->tags()->first();

        $tag->update(['name' => 'newName']);

        $this->assertEquals($tagName, $loadedTag->name);

        $this->assertEquals($tagName, $post->tags[0]->name);

        $loadedTag->refresh();

        $this->assertSame('newName', $loadedTag->name);

        $post->refresh();

        $this->assertSame('newName', $post->tags[0]->name);
    }

    public function testCustomPivotClass()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);

        $tag = TagWithCustomPivot::create(['name' => Str::random()]);

        $post->tagsWithCustomPivot()->attach($tag->id);

        $this->assertInstanceOf(PostTagPivot::class, $post->tagsWithCustomPivot[0]->pivot);
        $this->assertSame('1507630210', $post->tagsWithCustomPivot[0]->pivot->created_at);

        $this->assertInstanceOf(PostTagPivot::class, $post->tagsWithCustomPivotClass[0]->pivot);
        $this->assertSame('posts_tags', $post->tagsWithCustomPivotClass()->getTable());

        $this->assertEquals([
            'post_id' => '1',
            'tag_id' => '1',
        ], $post->tagsWithCustomAccessor[0]->tag->toArray());

        $pivot = $post->tagsWithCustomPivot[0]->pivot;
        $pivot->tag_id = 2;
        $pivot->save();

        $this->assertEquals(1, PostTagPivot::count());
        $this->assertEquals(1, PostTagPivot::first()->post_id);
        $this->assertEquals(2, PostTagPivot::first()->tag_id);
    }

    public function testCustomPivotClassUsingSync()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);

        $tag = TagWithCustomPivot::create(['name' => Str::random()]);

        $results = $post->tagsWithCustomPivot()->sync([
            $tag->id => ['flag' => 1],
        ]);

        $this->assertNotEmpty($results['attached']);

        $results = $post->tagsWithCustomPivot()->sync([
            $tag->id => ['flag' => 1],
        ]);

        $this->assertEmpty($results['updated']);

        $results = $post->tagsWithCustomPivot()->sync([]);

        $this->assertNotEmpty($results['detached']);
    }

    public function testCustomPivotClassUsingUpdateExistingPivot()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);
        $tag = TagWithCustomPivot::create(['name' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        // Test on actually existing pivot
        $this->assertEquals(
            1,
            $post->tagsWithCustomExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude'])
        );
        foreach ($post->tagsWithCustomExtraPivot as $tag) {
            $this->assertSame('exclude', $tag->pivot->flag);
        }

        // Test on non-existent pivot
        $this->assertEquals(
            0,
            $post->tagsWithCustomExtraPivot()->updateExistingPivot(0, ['flag' => 'exclude'])
        );
    }

    public function testCustomPivotClassUpdatesTimestamps()
    {
        Carbon::setTestNow('2017-10-10 10:10:10');

        $post = Post::create(['title' => Str::random()]);
        $tag = TagWithCustomPivot::create(['name' => Str::random()]);

        DB::table('posts_tags')->insert([
            [
                'post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty',
                'created_at' => '2017-10-10 10:10:10',
                'updated_at' => '2017-10-10 10:10:10',
            ],
        ]);

        Carbon::setTestNow('2017-10-10 10:10:20'); // +10 seconds

        $this->assertEquals(
            1,
            $post->tagsWithCustomExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude'])
        );
        foreach ($post->tagsWithCustomExtraPivot as $tag) {
            $this->assertSame('exclude', $tag->pivot->flag);

            if ($this->driver === 'sqlsrv') {
                $this->assertSame('2017-10-10 10:10:10.000', $tag->pivot->getAttributes()['created_at']);
                $this->assertSame('2017-10-10 10:10:20.000', $tag->pivot->getAttributes()['updated_at']); // +10 seconds
            } else {
                $this->assertSame('2017-10-10 10:10:10', $tag->pivot->getAttributes()['created_at']);
                $this->assertSame('2017-10-10 10:10:20', $tag->pivot->getAttributes()['updated_at']); // +10 seconds
            }
        }
    }

    public function testAttachMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);
        $tag5 = Tag::create(['name' => Str::random()]);
        $tag6 = Tag::create(['name' => Str::random()]);
        $tag7 = Tag::create(['name' => Str::random()]);
        $tag8 = Tag::create(['name' => Str::random()]);

        $post->tags()->attach($tag->id);
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertNotNull($post->tags[0]->pivot->created_at);

        $post->tags()->attach($tag2->id, ['flag' => 'taylor']);
        $post->load('tags');
        $this->assertEquals($tag2->name, $post->tags[1]->name);
        $this->assertSame('taylor', $post->tags[1]->pivot->flag);

        $post->tags()->attach([$tag3->id, $tag4->id]);
        $post->load('tags');
        $this->assertEquals($tag3->name, $post->tags[2]->name);
        $this->assertEquals($tag4->name, $post->tags[3]->name);

        $post->tags()->attach([$tag5->id => ['flag' => 'mohamed'], $tag6->id => ['flag' => 'adam']]);
        $post->load('tags');
        $this->assertEquals($tag5->name, $post->tags[4]->name);
        $this->assertSame('mohamed', $post->tags[4]->pivot->flag);
        $this->assertEquals($tag6->name, $post->tags[5]->name);
        $this->assertSame('adam', $post->tags[5]->pivot->flag);

        $post->tags()->attach(new Collection([$tag7, $tag8]));
        $post->load('tags');
        $this->assertEquals($tag7->name, $post->tags[6]->name);
        $this->assertEquals($tag8->name, $post->tags[7]->name);
    }

    public function testDetachMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);
        $tag5 = Tag::create(['name' => Str::random()]);
        Tag::create(['name' => Str::random()]);
        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals(Tag::pluck('name'), $post->tags->pluck('name'));

        $post->tags()->detach($tag->id);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $post->tags()->detach([$tag2->id, $tag3->id]);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $post->tags()->detach(new Collection([$tag4, $tag5]));
        $post->load('tags');
        $this->assertEquals(
            Tag::whereNotIn('id', [$tag->id, $tag2->id, $tag3->id, $tag4->id, $tag5->id])->pluck('name'),
            $post->tags->pluck('name')
        );

        $this->assertCount(2, $post->tags);
        $post->tags()->detach();
        $post->load('tags');
        $this->assertCount(0, $post->tags);
    }

    public function testFirstMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->name, $post->tags()->first()->name);
    }

    public function testFirstOrFailMethod()
    {
        $this->expectException(ModelNotFoundException::class);

        $post = Post::create(['title' => Str::random()]);

        $post->tags()->firstOrFail(['id']);
    }

    public function testFindMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag2->name, $post->tags()->find($tag2->id)->name);
        $this->assertCount(0, $post->tags()->findMany([]));
        $this->assertCount(2, $post->tags()->findMany([$tag->id, $tag2->id]));
        $this->assertCount(0, $post->tags()->findMany(new Collection));
        $this->assertCount(2, $post->tags()->findMany(new Collection([$tag->id, $tag2->id])));
    }

    public function testFindMethodStringyKey()
    {
        Schema::create('post_string_key', function (Blueprint $table) {
            $table->string('id', 1)->primary();
            $table->string('title', 10);
        });

        Schema::create('tag_string_key', function (Blueprint $table) {
            $table->string('id', 1)->primary();
            $table->string('title', 10);
        });

        Schema::create('post_tag_string_key', function (Blueprint $table) {
            $table->id();
            $table->string('post_id', 1);
            $table->string('tag_id', 1);
        });

        $post = PostStringPrimaryKey::query()->create([
            'id' => 'a',
            'title' => Str::random(10),
        ]);

        $tag = TagStringPrimaryKey::query()->create([
            'id' => 'b',
            'title' => Str::random(10),
        ]);

        $tag2 = TagStringPrimaryKey::query()->create([
            'id' => 'c',
            'title' => Str::random(10),
        ]);

        $post->tags()->attach(TagStringPrimaryKey::all());

        $this->assertEquals($tag2->name, $post->tags()->find($tag2->id)->name);
        $this->assertCount(0, $post->tags()->findMany([]));
        $this->assertCount(2, $post->tags()->findMany([$tag->id, $tag2->id]));
        $this->assertCount(0, $post->tags()->findMany(new Collection));
        $this->assertCount(2, $post->tags()->findMany(new Collection([$tag->id, $tag2->id])));
    }

    public function testFindOrFailMethod()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\Tag] 10');

        $post = Post::create(['title' => Str::random()]);

        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->findOrFail(10);
    }

    public function testFindOrFailMethodWithMany()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\Tag] 10, 11');

        $post = Post::create(['title' => Str::random()]);

        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->findOrFail([10, 11]);
    }

    public function testFindOrFailMethodWithManyUsingCollection()
    {
        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [Illuminate\Tests\Integration\Database\EloquentBelongsToManyTest\Tag] 10, 11');

        $post = Post::create(['title' => Str::random()]);

        Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->findOrFail(new Collection([10, 11]));
    }

    public function testFindOrNewMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->findOrNew($tag->id)->id);

        $this->assertNull($post->tags()->findOrNew(666)->id);
        $this->assertInstanceOf(Tag::class, $post->tags()->findOrNew(666));
    }

    public function testFindOrMethod()
    {
        $post = Post::create(['title' => Str::random()]);
        $post->tags()->create(['name' => Str::random()]);

        $result = $post->tags()->findOr(1, fn () => 'callback result');
        $this->assertInstanceOf(Tag::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertNotNull($result->name);

        $result = $post->tags()->findOr(1, ['id'], fn () => 'callback result');
        $this->assertInstanceOf(Tag::class, $result);
        $this->assertSame(1, $result->id);
        $this->assertNull($result->name);

        $result = $post->tags()->findOr(2, fn () => 'callback result');
        $this->assertSame('callback result', $result);
    }

    public function testFindOrMethodWithMany()
    {
        $post = Post::create(['title' => Str::random()]);
        $post->tags()->createMany([
            ['name' => Str::random()],
            ['name' => Str::random()],
        ]);

        $result = $post->tags()->findOr([1, 2], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame(2, $result[1]->id);
        $this->assertNotNull($result[0]->name);
        $this->assertNotNull($result[1]->name);

        $result = $post->tags()->findOr([1, 2], ['id'], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame(2, $result[1]->id);
        $this->assertNull($result[0]->name);
        $this->assertNull($result[1]->name);

        $result = $post->tags()->findOr([1, 2, 3], fn () => 'callback result');
        $this->assertSame('callback result', $result);
    }

    public function testFindOrMethodWithManyUsingCollection()
    {
        $post = Post::create(['title' => Str::random()]);
        $post->tags()->createMany([
            ['name' => Str::random()],
            ['name' => Str::random()],
        ]);

        $result = $post->tags()->findOr(new Collection([1, 2]), fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame(2, $result[1]->id);
        $this->assertNotNull($result[0]->name);
        $this->assertNotNull($result[1]->name);

        $result = $post->tags()->findOr(new Collection([1, 2]), ['id'], fn () => 'callback result');
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(1, $result[0]->id);
        $this->assertSame(2, $result[1]->id);
        $this->assertNull($result[0]->name);
        $this->assertNull($result[1]->name);

        $result = $post->tags()->findOr(new Collection([1, 2, 3]), fn () => 'callback result');
        $this->assertSame('callback result', $result);
    }

    public function testFirstOrNewMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->firstOrNew(['id' => $tag->id])->id);

        $this->assertNull($post->tags()->firstOrNew(['id' => 666])->id);
        $this->assertInstanceOf(Tag::class, $post->tags()->firstOrNew(['id' => 666]));
    }

    // public function testFirstOrNewUnrelatedExisting()
    // {
    //     $post = Post::create(['title' => Str::random()]);

    //     $name = Str::random();
    //     $tag = Tag::create(['name' => $name]);

    //     $postTag = $post->tags()->firstOrNew(['name' => $name]);
    //     $this->assertTrue($postTag->exists);
    //     $this->assertTrue($postTag->is($tag));
    //     $this->assertTrue($tag->is($post->tags()->first()));
    // }

    public function testFirstOrCreateMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $this->assertEquals($tag->id, $post->tags()->firstOrCreate(['name' => $tag->name])->id);

        $new = $post->tags()->firstOrCreate(['name' => 'wavez']);
        $this->assertSame('wavez', $new->name);
        $this->assertNotNull($new->id);
    }

    public function testFirstOrCreateUnrelatedExisting()
    {
        $post = Post::create(['title' => Str::random()]);

        $name = Str::random();
        $tag = Tag::create(['name' => $name]);

        $postTag = $post->tags()->firstOrCreate(['name' => $name]);
        $this->assertTrue($postTag->exists);
        $this->assertTrue($postTag->is($tag));
        $this->assertTrue($tag->is($post->tags()->first()));
    }

    public function testFirstOrNewMethodWithValues()
    {
        $post = Post::create(['title' => Str::random()]);
        $tag = Tag::create(['name' => Str::random()]);
        $post->tags()->attach(Tag::all());

        $existing = $post->tags()->firstOrNew(
            ['name' => $tag->name],
            ['type' => 'featured']
        );

        $this->assertEquals($tag->id, $existing->id);
        $this->assertNotEquals('foo', $existing->name);

        $new = $post->tags()->firstOrNew(
            ['name' => 'foo'],
            ['type' => 'featured']
        );

        $this->assertSame('foo', $new->name);
        $this->assertSame('featured', $new->type);

        $new = $post->tags()->firstOrNew(
            ['name' => 'foo'],
            ['name' => 'bar']
        );

        $this->assertSame('bar', $new->name);
    }

    public function testFirstOrCreateMethodWithValues()
    {
        $post = Post::create(['title' => Str::random()]);
        $tag = Tag::create(['name' => Str::random()]);
        $post->tags()->attach(Tag::all());

        $existing = $post->tags()->firstOrCreate(
            ['name' => $tag->name],
            ['type' => 'featured']
        );

        $this->assertEquals($tag->id, $existing->id);
        $this->assertNotEquals('foo', $existing->name);

        $new = $post->tags()->firstOrCreate(
            ['name' => 'foo'],
            ['type' => 'featured']
        );

        $this->assertSame('foo', $new->name);
        $this->assertSame('featured', $new->type);
        $this->assertNotNull($new->id);

        $new = $post->tags()->firstOrCreate(
            ['name' => 'qux'],
            ['name' => 'bar']
        );

        $this->assertSame('bar', $new->name);
        $this->assertNotNull($new->id);
    }

    public function testUpdateOrCreateMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);

        $post->tags()->attach(Tag::all());

        $post->tags()->updateOrCreate(['id' => $tag->id], ['name' => 'wavez']);
        $this->assertSame('wavez', $tag->fresh()->name);

        $post->tags()->updateOrCreate(['id' => 666], ['name' => 'dives']);
        $this->assertNotNull($post->tags()->whereName('dives')->first());
    }

    public function testUpdateOrCreateUnrelatedExisting()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => 'foo']);

        $postTag = $post->tags()->updateOrCreate(['name' => 'foo'], ['name' => 'wavez']);
        $this->assertTrue($postTag->exists);
        $this->assertTrue($postTag->is($tag));
        $this->assertSame('wavez', $tag->fresh()->name);
        $this->assertSame('wavez', $postTag->name);
        $this->assertTrue($tag->is($post->tags()->first()));
    }

    public function testUpdateOrCreateMethodCreate()
    {
        $post = Post::create(['title' => Str::random()]);

        $post->tags()->updateOrCreate(['name' => 'wavez'], ['type' => 'featured']);

        $tag = $post->tags()->whereType('featured')->first();

        $this->assertNotNull($tag);
        $this->assertSame('wavez', $tag->name);
    }

    public function testSyncMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $tag4 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([$tag->id, $tag2->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $output = $post->tags()->sync([$tag->id, $tag3->id, $tag4->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag3->id, $tag4->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $this->assertEquals([
            'attached' => [$tag3->id, $tag4->id],
            'detached' => [1 => $tag2->id],
            'updated' => [],
        ], $output);

        $post->tags()->sync([]);
        $this->assertEmpty($post->load('tags')->tags);

        $post->tags()->sync([
            $tag->id => ['flag' => 'taylor'],
            $tag2->id => ['flag' => 'mohamed'],
        ]);
        $post->load('tags');
        $this->assertEquals($tag->name, $post->tags[0]->name);
        $this->assertSame('taylor', $post->tags[0]->pivot->flag);
        $this->assertEquals($tag2->name, $post->tags[1]->name);
        $this->assertSame('mohamed', $post->tags[1]->pivot->flag);
    }

    public function testSyncWithoutDetachingMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->sync([$tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->syncWithoutDetaching([$tag2->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id, $tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );
    }

    public function testToggleMethod()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);

        $post->tags()->toggle([$tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->toggle([$tag2->id, $tag->id]);

        $this->assertEquals(
            Tag::whereIn('id', [$tag2->id])->pluck('name'),
            $post->load('tags')->tags->pluck('name')
        );

        $post->tags()->toggle([$tag2->id, $tag->id => ['flag' => 'taylor']]);
        $post->load('tags');
        $this->assertEquals(
            Tag::whereIn('id', [$tag->id])->pluck('name'),
            $post->tags->pluck('name')
        );
        $this->assertSame('taylor', $post->tags[0]->pivot->flag);
    }

    public function testTouchingParent()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = TouchingTag::create(['name' => Str::random()]);

        $post->touchingTags()->attach([$tag->id]);

        $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->update(['name' => $tag->name]);
        $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());

        $tag->update(['name' => Str::random()]);
        $this->assertSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
    }

    public function testTouchingRelatedModelsOnSync()
    {
        $tag = TouchingTag::create(['name' => Str::random()]);

        $post = Post::create(['title' => Str::random()]);

        $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->posts()->sync([$post->id]);

        $this->assertSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
    }

    public function testNoTouchingHappensIfNotConfigured()
    {
        $tag = Tag::create(['name' => Str::random()]);

        $post = Post::create(['title' => Str::random()]);

        $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());

        Carbon::setTestNow('2017-10-10 10:10:10');

        $tag->posts()->sync([$post->id]);

        $this->assertNotSame('2017-10-10 10:10:10', $post->fresh()->updated_at->toDateTimeString());
        $this->assertNotSame('2017-10-10 10:10:10', $tag->fresh()->updated_at->toDateTimeString());
    }

    public function testCanRetrieveRelatedIds()
    {
        $post = Post::create(['title' => Str::random()]);

        DB::table('tags')->insert([
            ['name' => 'excluded'],
            ['name' => Str::random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 1, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 2, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 3, 'flag' => ''],
        ]);

        $this->assertEquals([1, 3], $post->tags()->allRelatedIds()->toArray());
    }

    public function testCanTouchRelatedModels()
    {
        $post = Post::create(['title' => Str::random()]);

        DB::table('tags')->insert([
            ['name' => Str::random()],
            ['name' => Str::random()],
        ]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => 1, 'flag' => ''],
            ['post_id' => $post->id, 'tag_id' => 2, 'flag' => 'exclude'],
            ['post_id' => $post->id, 'tag_id' => 3, 'flag' => ''],
        ]);

        Carbon::setTestNow('2017-10-10 10:10:10');

        $post->tags()->touch();

        foreach ($post->tags()->pluck('tags.updated_at') as $date) {
            if ($this->driver === 'sqlsrv') {
                $this->assertSame('2017-10-10 10:10:10.000', $date);
            } else {
                $this->assertSame('2017-10-10 10:10:10', $date);
            }
        }

        $this->assertNotSame('2017-10-10 10:10:10', Tag::find(2)->updated_at);
    }

    public function testWherePivotOnString()
    {
        $tag = Tag::create(['name' => Str::random()])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
        ]);

        $relationTag = $post->tags()->wherePivot('flag', 'foo')->first();
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

        $relationTag = $post->tags()->wherePivot('flag', '=', 'foo')->first();
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
    }

    public function testFirstWhere()
    {
        $tag = Tag::create(['name' => 'foo'])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
        ]);

        $relationTag = $post->tags()->firstWhere('name', 'foo');
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

        $relationTag = $post->tags()->firstWhere('name', '=', 'foo');
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
    }

    public function testWherePivotOnBoolean()
    {
        $tag = Tag::create(['name' => Str::random()])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => true],
        ]);

        $relationTag = $post->tags()->wherePivot('flag', true)->first();
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());

        $relationTag = $post->tags()->wherePivot('flag', '=', true)->first();
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
    }

    public function testWherePivotInMethod()
    {
        $tag = Tag::create(['name' => Str::random()])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'foo'],
        ]);

        $relationTag = $post->tags()->wherePivotIn('flag', ['foo'])->first();
        $this->assertEquals($relationTag->getAttributes(), $tag->getAttributes());
    }

    public function testOrWherePivotInMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'baz'],
        ]);

        $relationTags = $post->tags()->wherePivotIn('flag', ['foo'])->orWherePivotIn('flag', ['baz'])->get();
        $this->assertEquals($relationTags->pluck('id')->toArray(), [$tag1->id, $tag3->id]);
    }

    public function testWherePivotNotInMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
        ]);

        $relationTag = $post->tags()->wherePivotNotIn('flag', ['foo'])->first();
        $this->assertEquals($relationTag->getAttributes(), $tag2->getAttributes());
    }

    public function testOrWherePivotNotInMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()]);
        $tag3 = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'bar'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'baz'],
        ]);

        $relationTags = $post->tags()->wherePivotIn('flag', ['foo'])->orWherePivotNotIn('flag', ['baz'])->get();
        $this->assertEquals($relationTags->pluck('id')->toArray(), [$tag1->id, $tag2->id]);
    }

    public function testWherePivotNullMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()])->fresh();
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => null],
        ]);

        $relationTag = $post->tagsWithExtraPivot()->wherePivotNull('flag')->first();
        $this->assertEquals($relationTag->getAttributes(), $tag2->getAttributes());
    }

    public function testWherePivotNotNullMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()])->fresh();
        $tag2 = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo'],
        ]);
        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => null],
        ]);

        $relationTag = $post->tagsWithExtraPivot()->wherePivotNotNull('flag')->first();
        $this->assertEquals($relationTag->getAttributes(), $tag1->getAttributes());
    }

    public function testCanUpdateExistingPivot()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tag->id, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertSame('exclude', $tag->pivot->flag);
        }
    }

    public function testCanUpdateExistingPivotUsingArrayableOfIds()
    {
        $tags = new Collection([
            $tag1 = Tag::create(['name' => Str::random()]),
            $tag2 = Tag::create(['name' => Str::random()]),
        ]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'empty'],
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tags, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertSame('exclude', $tag->pivot->flag);
        }
    }

    public function testCanUpdateExistingPivotUsingModel()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $post->tagsWithExtraPivot()->updateExistingPivot($tag, ['flag' => 'exclude']);

        foreach ($post->tagsWithExtraPivot as $tag) {
            $this->assertSame('exclude', $tag->pivot->flag);
        }
    }

    public function testCustomRelatedKey()
    {
        $post = Post::create(['title' => Str::random()]);

        $tag = $post->tagsWithCustomRelatedKey()->create(['name' => Str::random()]);
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

        $post->tagsWithCustomRelatedKey()->detach($tag);

        $post->tagsWithCustomRelatedKey()->attach($tag);
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

        $post->tagsWithCustomRelatedKey()->detach(new Collection([$tag]));

        $post->tagsWithCustomRelatedKey()->attach(new Collection([$tag]));
        $this->assertEquals($tag->name, $post->tagsWithCustomRelatedKey()->first()->pivot->tag_name);

        $post->tagsWithCustomRelatedKey()->updateExistingPivot($tag, ['flag' => 'exclude']);
        $this->assertSame('exclude', $post->tagsWithCustomRelatedKey()->first()->pivot->flag);
    }

    public function testGlobalScopeColumns()
    {
        $tag = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag->id, 'flag' => 'empty'],
        ]);

        $tags = $post->tagsWithGlobalScope;

        $this->assertEquals(['id' => 1], $tags[0]->getAttributes());
    }

    public function testPivotDoesntHavePrimaryKey()
    {
        $user = User::create(['name' => Str::random()]);
        $post1 = Post::create(['title' => Str::random()]);
        $post2 = Post::create(['title' => Str::random()]);

        $user->postsWithCustomPivot()->sync([$post1->uuid]);
        $this->assertEquals($user->uuid, $user->postsWithCustomPivot()->first()->pivot->user_uuid);
        $this->assertEquals($post1->uuid, $user->postsWithCustomPivot()->first()->pivot->post_uuid);
        $this->assertEquals(1, $user->postsWithCustomPivot()->first()->pivot->is_draft);

        $user->postsWithCustomPivot()->sync([$post2->uuid]);
        $this->assertEquals($user->uuid, $user->postsWithCustomPivot()->first()->pivot->user_uuid);
        $this->assertEquals($post2->uuid, $user->postsWithCustomPivot()->first()->pivot->post_uuid);
        $this->assertEquals(1, $user->postsWithCustomPivot()->first()->pivot->is_draft);

        $user->postsWithCustomPivot()->updateExistingPivot($post2->uuid, ['is_draft' => 0]);
        $this->assertEquals(0, $user->postsWithCustomPivot()->first()->pivot->is_draft);
    }

    public function testOrderByPivotMethod()
    {
        $tag1 = Tag::create(['name' => Str::random()]);
        $tag2 = Tag::create(['name' => Str::random()])->fresh();
        $tag3 = Tag::create(['name' => Str::random()])->fresh();
        $tag4 = Tag::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        DB::table('posts_tags')->insert([
            ['post_id' => $post->id, 'tag_id' => $tag1->id, 'flag' => 'foo3'],
            ['post_id' => $post->id, 'tag_id' => $tag2->id, 'flag' => 'foo1'],
            ['post_id' => $post->id, 'tag_id' => $tag3->id, 'flag' => 'foo4'],
            ['post_id' => $post->id, 'tag_id' => $tag4->id, 'flag' => 'foo2'],
        ]);

        $relationTag1 = $post->tagsWithCustomExtraPivot()->orderByPivot('flag', 'asc')->first();
        $this->assertEquals($relationTag1->getAttributes(), $tag2->getAttributes());

        $relationTag2 = $post->tagsWithCustomExtraPivot()->orderByPivot('flag', 'desc')->first();
        $this->assertEquals($relationTag2->getAttributes(), $tag3->getAttributes());
    }

    public function testFirstOrMethod()
    {
        $user1 = User::create(['name' => Str::random()]);
        $user2 = User::create(['name' => Str::random()]);
        $user3 = User::create(['name' => Str::random()]);
        $post1 = Post::create(['title' => Str::random()]);
        $post2 = Post::create(['title' => Str::random()]);
        $post3 = Post::create(['title' => Str::random()]);

        $user1->posts()->sync([$post1->uuid, $post2->uuid]);
        $user2->posts()->sync([$post1->uuid, $post2->uuid]);

        $this->assertEquals(
            $post1->id,
            $user2->posts()->firstOr(function () {
                return Post::create(['title' => Str::random()]);
            })->id
        );

        $this->assertEquals(
            $post3->id,
            $user3->posts()->firstOr(function () use ($post3) {
                return $post3;
            })->id
        );
    }

    public function testUpdateOrCreateQueryBuilderIsolation()
    {
        $user = User::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        $user->postsWithCustomPivot()->attach($post);

        $instance = $user->postsWithCustomPivot()->updateOrCreate(
            ['uuid' => $post->uuid],
            ['title' => Str::random()],
        );

        $this->assertArrayNotHasKey(
            'user_uuid',
            $instance->toArray(),
        );
    }

    public function testFirstOrCreateQueryBuilderIsolation()
    {
        $user = User::create(['name' => Str::random()]);
        $post = Post::create(['title' => Str::random()]);

        $user->postsWithCustomPivot()->attach($post);

        $instance = $user->postsWithCustomPivot()->firstOrCreate(
            ['uuid' => $post->uuid],
            ['title' => Str::random()],
        );

        $this->assertArrayNotHasKey(
            'user_uuid',
            $instance->toArray(),
        );
    }
}

class User extends Model
{
    public $table = 'users';
    public $timestamps = true;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::random());
        });
    }

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'users_posts', 'user_uuid', 'post_uuid', 'uuid', 'uuid')
            ->withPivot('is_draft')
            ->withTimestamps();
    }

    public function postsWithCustomPivot()
    {
        return $this->belongsToMany(Post::class, 'users_posts', 'user_uuid', 'post_uuid', 'uuid', 'uuid')
            ->using(UserPostPivot::class)
            ->withPivot('is_draft')
            ->withTimestamps();
    }
}

class PostStringPrimaryKey extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'post_string_key';

    protected $keyType = 'string';

    protected $fillable = ['title', 'id'];

    public function tags()
    {
        return $this->belongsToMany(TagStringPrimaryKey::class, 'post_tag_string_key', 'post_id', 'tag_id');
    }
}

class TagStringPrimaryKey extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'tag_string_key';

    protected $keyType = 'string';

    protected $fillable = ['title', 'id'];

    public function posts()
    {
        return $this->belongsToMany(PostStringPrimaryKey::class, 'post_tag_string_key', 'tag_id', 'post_id');
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $touches = ['touchingTags'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::random());
        });
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'users_posts', 'post_uuid', 'user_uuid', 'uuid', 'uuid')
            ->withPivot('is_draft')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag')
            ->withTimestamps()
            ->wherePivot('flag', '<>', 'exclude');
    }

    public function tagsWithExtraPivot()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag');
    }

    public function touchingTags()
    {
        return $this->belongsToMany(TouchingTag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withTimestamps();
    }

    public function tagsWithCustomPivot()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->withTimestamps();
    }

    public function tagsWithCustomExtraPivot()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->withTimestamps()
            ->withPivot('flag');
    }

    public function tagsWithCustomPivotClass()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, PostTagPivot::class, 'post_id', 'tag_id');
    }

    public function tagsWithCustomAccessor()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->as('tag');
    }

    public function tagsWithCustomRelatedKey()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_name', 'id', 'name')
            ->withPivot('flag');
    }

    public function tagsWithGlobalScope()
    {
        return $this->belongsToMany(TagWithGlobalScope::class, 'posts_tags', 'post_id', 'tag_id');
    }
}

class Tag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $fillable = ['name', 'type'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class TouchingTag extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = [];
    protected $touches = ['posts'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class TagWithCustomPivot extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = [];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'posts_tags', 'tag_id', 'post_id');
    }
}

class UserPostPivot extends Pivot
{
    protected $table = 'users_posts';
}

class PostTagPivot extends Pivot
{
    protected $table = 'posts_tags';

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('U');
    }
}

class TagWithGlobalScope extends Model
{
    public $table = 'tags';
    public $timestamps = true;
    protected $guarded = [];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->select('tags.id');
        });
    }
}
