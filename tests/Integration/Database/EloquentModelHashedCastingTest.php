<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class EloquentModelHashedCastingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('hashed_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('password')->nullable();
        });
    }

    public function testHashed()
    {
        Hash::expects('isHashed')
            ->with('this is a password')
            ->andReturnFalse();

        Hash::expects('make')
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
        Hash::expects('isHashed')
            ->with('already-hashed-password')
            ->andReturnTrue();

        Hash::expects('needsRehash')
            ->with('already-hashed-password')
            ->andReturnFalse();

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

    public function testPassingHashWithHigherCostThrowsException()
    {
        Config::set('hashing.bcrypt.rounds', 10);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The hash does not match the configured hashing options.');

        $subject = HashedCast::create([
            // "password"; 13 rounds; bcrypt;
            'password' => '$2y$13$Hdxlvi7OZqK3/fKVNypJs.vJqQcmOo3HnnT6w7fec9FRTRYxAhuCO',
        ]);
    }

    public function testPassingHashWithLowerCostThrowsException()
    {
        Config::set('hashing.bcrypt.rounds', 10);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The hash does not match the configured hashing options.');

        $subject = HashedCast::create([
            // "password"; 7 rounds; bcrypt;
            'password' => '$2y$07$Ivc2VnUOUFtfdbXFc/Ysu.PgiwAHkDmbZQNR1OpIjKCxTxEfWLP5y',
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
