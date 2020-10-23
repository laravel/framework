<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * @group integration
 */
class EloquentModelEncryptedCastingTest extends DatabaseTestCase
{
    protected $encrypter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encrypter = $this->mock(Encrypter::class);
        Crypt::swap($this->encrypter);

        Schema::create('encrypted_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('secret', 1000)->nullable();
        });
    }

    public function testStringsAreCastable()
    {
        $this->encrypter->expects('encryptString')
            ->with('this is a secret string')
            ->andReturn('encrypted-secret-string');
        $this->encrypter->expects('decryptString')
            ->with('encrypted-secret-string')
            ->andReturn('this is a secret string');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $object */
        $object = EncryptedCast::create([
            'secret' => 'this is a secret string',
        ]);

        $this->assertSame('this is a secret string', $object->secret);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $object->id,
            'secret' => 'encrypted-secret-string',
        ]);
    }
}

/**
 * @property $secret
 */
class EncryptedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'secret' => 'encrypted',
    ];
}
