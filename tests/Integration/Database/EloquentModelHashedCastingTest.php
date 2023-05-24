<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class EloquentModelHashedCastingTest extends DatabaseTestCase
{
    protected $hasher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->hasher = $this->mock(Hasher::class);
        Hash::swap($this->hasher);
    }

    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('hashed_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('password')->nullable();
        });
    }

    public function testHashed()
    {
        $this->hasher->expects('isHashed')
            ->with('this is a password')
            ->andReturnFalse();

        $this->hasher->expects('make')
            ->with('this is a password')
            ->andReturn('hashed-password');

        $subject = HashedCast::create([
            'password' => 'this is a password',
        ]);

        $this->assertSame('hashed-password', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => 'hashed-password',
        ]);
    }

    public function testNotHashedIfAlreadyHashed()
    {
        $this->hasher->expects('isHashed')
            ->with('already-hashed-password')
            ->andReturnTrue();

        $subject = HashedCast::create([
            'password' => 'already-hashed-password',
        ]);

        $this->assertSame('already-hashed-password', $subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => 'already-hashed-password',
        ]);
    }

    public function testNotHashedIfNull()
    {
        $subject = HashedCast::create([
            'password' => null,
        ]);

        $this->assertNull($subject->password);
        $this->assertDatabaseHas('hashed_casts', [
            'id' => $subject->id,
            'password' => null,
        ]);
    }
}

class HashedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'password' => 'hashed',
    ];
}
