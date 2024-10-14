<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EloquentUniqueStringPrimaryKeysTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('foo');
            $table->uuid('bar');
            $table->timestamps();
        });

        Schema::create('foo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('foo');
            $table->ulid('bar');
            $table->timestamps();
        });

        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->uuid('foo');
            $table->uuid('bar');
            $table->timestamps();
        });

        Schema::create('pictures', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->timestamps();
        });
    }

    public function testModelWithUuidPrimaryKeyCanBeCreated()
    {
        $user = ModelWithUuidPrimaryKey::create();

        $this->assertTrue(Str::isUuid($user->id));
        $this->assertTrue(Str::isUuid($user->foo));
        $this->assertTrue(Str::isUuid($user->bar));
    }

    public function testModelWithUlidPrimaryKeyCanBeCreated()
    {
        $user = ModelWithUlidPrimaryKey::create();

        $this->assertTrue(Str::isUlid($user->id));
        $this->assertTrue(Str::isUlid($user->foo));
        $this->assertTrue(Str::isUlid($user->bar));
    }

    public function testModelWithoutUuidPrimaryKeyCanBeCreated()
    {
        $user = ModelWithoutUuidPrimaryKey::create();

        $this->assertTrue(is_int($user->id));
        $this->assertTrue(Str::isUuid($user->foo));
        $this->assertTrue(Str::isUuid($user->bar));
    }

    public function testModelWithCustomUuidPrimaryKeyNameCanBeCreated()
    {
        $user = ModelWithCustomUuidPrimaryKeyName::create();

        $this->assertTrue(Str::isUuid($user->uuid));
    }

    public function testModelWithUuidPrimaryKeyCanBeCreatedQuietly()
    {
        $user = new ModelWithUuidPrimaryKey();

        $user->saveQuietly();

        $this->assertTrue(Str::isUuid($user->id));
        $this->assertTrue(Str::isUuid($user->foo));
        $this->assertTrue(Str::isUuid($user->bar));
    }

    public function testModelWithUlidPrimaryKeyCanBeCreatedQuietly()
    {
        $user = new ModelWithUlidPrimaryKey();

        $user->saveQuietly();

        $this->assertTrue(Str::isUlid($user->id));
        $this->assertTrue(Str::isUlid($user->foo));
        $this->assertTrue(Str::isUlid($user->bar));
    }

    public function testModelWithoutUuidPrimaryKeyCanBeCreatedQuietly()
    {
        $user = new ModelWithoutUuidPrimaryKey();

        $user->saveQuietly();

        $this->assertTrue(is_int($user->id));
        $this->assertTrue(Str::isUuid($user->foo));
        $this->assertTrue(Str::isUuid($user->bar));
    }

    public function testModelWithCustomUuidPrimaryKeyNameCanBeCreatedQuietly()
    {
        $user = new ModelWithCustomUuidPrimaryKeyName();

        $user->saveQuietly();

        $this->assertTrue(Str::isUuid($user->uuid));
    }

    public function testUpsertWithUuidPrimaryKey()
    {
        ModelUpsertWithUuidPrimaryKey::create(['email' => 'foo', 'name' => 'bar']);
        ModelUpsertWithUuidPrimaryKey::create(['name' => 'bar1', 'email' => 'foo2']);

        ModelUpsertWithUuidPrimaryKey::upsert([['email' => 'foo3', 'name' => 'bar'], ['name' => 'bar2', 'email' => 'foo2']], ['email']);

        $this->assertEquals(3, ModelUpsertWithUuidPrimaryKey::count());
    }
}

class ModelWithUuidPrimaryKey extends Eloquent
{
    use HasUuids;

    protected $table = 'users';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName(), 'foo', 'bar'];
    }
}

class ModelUpsertWithUuidPrimaryKey extends Eloquent
{
    use HasUuids;

    protected $table = 'foo';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName()];
    }
}

class ModelWithUlidPrimaryKey extends Eloquent
{
    use HasUlids;

    protected $table = 'posts';

    protected $guarded = [];

    public function uniqueIds()
    {
        return [$this->getKeyName(), 'foo', 'bar'];
    }
}

class ModelWithoutUuidPrimaryKey extends Eloquent
{
    use HasUuids;

    protected $table = 'songs';

    protected $guarded = [];

    public function uniqueIds()
    {
        return ['foo', 'bar'];
    }
}

class ModelWithCustomUuidPrimaryKeyName extends Eloquent
{
    use HasUuids;

    protected $table = 'pictures';

    protected $guarded = [];

    protected $primaryKey = 'uuid';
}
