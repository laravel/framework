<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentGeneratedAsFillTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
            $table->id()->generatedAs()->always();
            $table->string('email')->unique();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function testForceFillAfterCreationNotThrowsException()
    {
        $user = TestUserWithIdendityId::create([
            'email' => 'matteo@email.it',
            'name' => 'Matteo',
        ]);

        $this->assertNotNull($user->trial_ends_at);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
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

/**
 * Eloquent Models...
 */
class TestUserWithIdendityId extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function casts()
    {
        return [
            'trial_ends_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::created(function ($user) {
            $user->forceFill([
                'trials_end_at' => now()->addDays(7),
            ])->save();
            
            return $user;
        });
    }
}
