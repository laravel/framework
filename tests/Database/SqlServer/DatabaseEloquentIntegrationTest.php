<?php

namespace Illuminate\Tests\Database\SqlServer;

use DateTimeInterface;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Facades\Date;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentIntegrationTest extends TestCase
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

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
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

        $this->schema('default')->create('with_json', function ($table) {
            $table->increments('id');
            $table->text('json')->default(json_encode([]));
        });

        $this->schema('second_connection')->create('test_items', function ($table) {
            $table->increments('id');
            $table->timestamps();
        });

        $this->schema('default')->create('users_with_space_in_colum_name', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('email address');
            $table->timestamps();
        });

        foreach (['default', 'second_connection'] as $connection) {
            $this->schema($connection)->create('users', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('email');
                $table->timestamp('birthday', 6)->nullable();
                $table->timestamps();
            });

            $this->schema($connection)->create('friends', function ($table) {
                $table->integer('user_id');
                $table->integer('friend_id');
                $table->integer('friend_level_id')->nullable();
            });

            $this->schema($connection)->create('posts', function ($table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('parent_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });

            $this->schema($connection)->create('comments', function ($table) {
                $table->increments('id');
                $table->integer('post_id');
                $table->string('content');
                $table->timestamps();
            });

            $this->schema($connection)->create('friend_levels', function ($table) {
                $table->increments('id');
                $table->string('level');
                $table->timestamps();
            });

            $this->schema($connection)->create('photos', function ($table) {
                $table->increments('id');
                $table->morphs('imageable');
                $table->string('name');
                $table->timestamps();
            });

            $this->schema($connection)->create('soft_deleted_users', function ($table) {
                $table->increments('id');
                $table->string('name')->nullable();
                $table->string('email');
                $table->timestamps();
                $table->softDeletes();
            });

            $this->schema($connection)->create('tags', function ($table) {
                $table->increments('id');
                $table->string('name');
                $table->timestamps();
            });

            $this->schema($connection)->create('taggables', function ($table) {
                $table->integer('tag_id');
                $table->morphs('taggable');
                $table->string('taxonomy')->nullable();
            });
        }

        $this->schema($connection)->create('non_incrementing_users', function ($table) {
            $table->string('name')->nullable();
        });
    }

    public function testTimestampsUsingDefaultDateFormat()
    {
        $model = new EloquentTestUser;
        $model->setDateFormat('Y-m-d H:i:s.v'); // Default SQL Server date format
        $model->setRawAttributes([
            'created_at' => '2017-11-14 08:23:19.000',
            'updated_at' => '2017-11-14 08:23:19.734',
        ]);

        $this->assertSame('2017-11-14 08:23:19.000', $model->fromDateTime($model->getAttribute('created_at')));
        $this->assertSame('2017-11-14 08:23:19.734', $model->fromDateTime($model->getAttribute('updated_at')));
    }

    public function testTimestampsUsingOldDateFormat()
    {
        $model = new EloquentTestUser;
        $model->setDateFormat('Y-m-d H:i:s.000'); // Old SQL Server date format
        $model->setRawAttributes([
            'created_at' => '2017-11-14 08:23:19.000',
        ]);

        $this->assertSame('2017-11-14 08:23:19.000', $model->fromDateTime($model->getAttribute('created_at')));
    }

    public function testTimestampsUsingOldDateFormatFallbackToDefaultParsing()
    {
        $model = new EloquentTestUser;
        $model->setDateFormat('Y-m-d H:i:s.000'); // Old SQL Server date format
        $model->setRawAttributes([
            'updated_at' => '2017-11-14 08:23:19.734',
        ]);

        $date = $model->getAttribute('updated_at');
        $this->assertSame('2017-11-14 08:23:19.734', $date->format('Y-m-d H:i:s.v'), 'the date should contains the precision');
        $this->assertSame('2017-11-14 08:23:19.000', $model->fromDateTime($date), 'the format should trims it');
        // No longer throwing exception since Laravel 7,
        // but Date::hasFormat() can be used instead to check date formatting:
        $this->assertTrue(Date::hasFormat('2017-11-14 08:23:19.000', $model->getDateFormat()));
        $this->assertFalse(Date::hasFormat('2017-11-14 08:23:19.734', $model->getDateFormat()));
    }

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

/**
 * Eloquent Models...
 */
class EloquentTestUser extends Eloquent
{
    protected $table = 'users';
    protected $casts = ['birthday' => 'datetime'];
    protected $guarded = [];

    public function friends()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id');
    }

    public function friendsOne()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 1);
    }

    public function friendsTwo()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 2);
    }

    public function posts()
    {
        return $this->hasMany(EloquentTestPost::class, 'user_id');
    }

    public function post()
    {
        return $this->hasOne(EloquentTestPost::class, 'user_id');
    }

    public function photos()
    {
        return $this->morphMany(EloquentTestPhoto::class, 'imageable');
    }

    public function postWithPhotos()
    {
        return $this->post()->join('photo', function ($join) {
            $join->on('photo.imageable_id', 'post.id');
            $join->where('photo.imageable_type', 'EloquentTestPost');
        });
    }
}
