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
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('foo');
            $table->uuid('bar');
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
