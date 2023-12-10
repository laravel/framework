<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use stdClass;

class EloquentModelEncryptedCastingTest extends DatabaseTestCase
{
    protected $encrypter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encrypter = $this->mock(Encrypter::class);
        Crypt::swap($this->encrypter);

        Model::$encrypter = null;
    }

    protected function afterRefreshingDatabase()
    {
        Schema::create('encrypted_casts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('secret', 1000)->nullable();
            $table->text('secret_array')->nullable();
            $table->text('secret_json')->nullable();
            $table->text('secret_object')->nullable();
            $table->text('secret_collection')->nullable();
        });
    }

    public function testStringsAreCastable()
    {
        $this->encrypter->expects('encrypt')
            ->with('this is a secret string', false)
            ->andReturn('encrypted-secret-string');
        $this->encrypter->expects('decrypt')
            ->with('encrypted-secret-string', false)
            ->andReturn('this is a secret string');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret' => 'this is a secret string',
        ]);

        $this->assertSame('this is a secret string', $subject->secret);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret' => 'encrypted-secret-string',
        ]);
    }

    public function testArraysAreCastable()
    {
        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1"}', false)
            ->andReturn('encrypted-secret-array-string');
        $this->encrypter->expects('decrypt')
            ->with('encrypted-secret-array-string', false)
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_array' => ['key1' => 'value1'],
        ]);

        $this->assertSame(['key1' => 'value1'], $subject->secret_array);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_array' => 'encrypted-secret-array-string',
        ]);
    }

    public function testJsonIsCastable()
    {
        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1"}', false)
            ->andReturn('encrypted-secret-json-string');
        $this->encrypter->expects('decrypt')
            ->with('encrypted-secret-json-string', false)
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_json' => ['key1' => 'value1'],
        ]);

        $this->assertSame(['key1' => 'value1'], $subject->secret_json);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_json' => 'encrypted-secret-json-string',
        ]);
    }

    public function testJsonAttributeIsCastable()
    {
        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1"}', false)
            ->andReturn('encrypted-secret-json-string');
        $this->encrypter->expects('decrypt')
            ->with('encrypted-secret-json-string', false)
            ->andReturn('{"key1":"value1"}');
        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1","key2":"value2"}', false)
            ->andReturn('encrypted-secret-json-string2');
        $this->encrypter->expects('decrypt')
            ->with('encrypted-secret-json-string2', false)
            ->andReturn('{"key1":"value1","key2":"value2"}');

        $subject = new EncryptedCast([
            'secret_json' => ['key1' => 'value1'],
        ]);
        $subject->fill([
            'secret_json->key2' => 'value2',
        ]);
        $subject->save();

        $this->assertSame(['key1' => 'value1', 'key2' => 'value2'], $subject->secret_json);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_json' => 'encrypted-secret-json-string2',
        ]);
    }

    public function testObjectIsCastable()
    {
        $object = new stdClass;
        $object->key1 = 'value1';

        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1"}', false)
            ->andReturn('encrypted-secret-object-string');
        $this->encrypter->expects('decrypt')
            ->twice()
            ->with('encrypted-secret-object-string', false)
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $object */
        $object = EncryptedCast::create([
            'secret_object' => $object,
        ]);

        $this->assertInstanceOf(stdClass::class, $object->secret_object);
        $this->assertSame('value1', $object->secret_object->key1);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $object->id,
            'secret_object' => 'encrypted-secret-object-string',
        ]);
    }

    public function testCollectionIsCastable()
    {
        $this->encrypter->expects('encrypt')
            ->with('{"key1":"value1"}', false)
            ->andReturn('encrypted-secret-collection-string');
        $this->encrypter->expects('decrypt')
            ->twice()
            ->with('encrypted-secret-collection-string', false)
            ->andReturn('{"key1":"value1"}');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret_collection' => new Collection(['key1' => 'value1']),
        ]);

        $this->assertInstanceOf(Collection::class, $subject->secret_collection);
        $this->assertSame('value1', $subject->secret_collection->get('key1'));
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_collection' => 'encrypted-secret-collection-string',
        ]);
    }

    public function testAsEncryptedCollection()
    {
        $this->encrypter->expects('encryptString')
            ->twice()
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-collection-string-1');
        $this->encrypter->expects('encryptString')
            ->times(10)
            ->with('{"key1":"value1","key2":"value2"}')
            ->andReturn('encrypted-secret-collection-string-2');
        $this->encrypter->expects('decryptString')
            ->once()
            ->with('encrypted-secret-collection-string-2')
            ->andReturn('{"key1":"value1","key2":"value2"}');

        $subject = new EncryptedCast;

        $subject->mergeCasts(['secret_collection' => AsEncryptedCollection::class]);

        $subject->secret_collection = new Collection(['key1' => 'value1']);
        $subject->secret_collection->put('key2', 'value2');

        $subject->save();

        $this->assertInstanceOf(Collection::class, $subject->secret_collection);
        $this->assertSame('value1', $subject->secret_collection->get('key1'));
        $this->assertSame('value2', $subject->secret_collection->get('key2'));
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_collection' => 'encrypted-secret-collection-string-2',
        ]);

        $subject = $subject->fresh();

        $this->assertInstanceOf(Collection::class, $subject->secret_collection);
        $this->assertSame('value1', $subject->secret_collection->get('key1'));
        $this->assertSame('value2', $subject->secret_collection->get('key2'));

        $subject->secret_collection = null;
        $subject->save();

        $this->assertNull($subject->secret_collection);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_collection' => null,
        ]);

        $this->assertNull($subject->fresh()->secret_collection);
    }

    public function testAsEncryptedArrayObject()
    {
        $this->encrypter->expects('encryptString')
            ->once()
            ->with('{"key1":"value1"}')
            ->andReturn('encrypted-secret-array-string-1');
        $this->encrypter->expects('decryptString')
            ->once()
            ->with('encrypted-secret-array-string-1')
            ->andReturn('{"key1":"value1"}');
        $this->encrypter->expects('encryptString')
            ->times(10)
            ->with('{"key1":"value1","key2":"value2"}')
            ->andReturn('encrypted-secret-array-string-2');
        $this->encrypter->expects('decryptString')
            ->once()
            ->with('encrypted-secret-array-string-2')
            ->andReturn('{"key1":"value1","key2":"value2"}');

        $subject = new EncryptedCast;

        $subject->mergeCasts(['secret_array' => AsEncryptedArrayObject::class]);

        $subject->secret_array = ['key1' => 'value1'];
        $subject->secret_array['key2'] = 'value2';

        $subject->save();

        $this->assertInstanceOf(ArrayObject::class, $subject->secret_array);
        $this->assertSame('value1', $subject->secret_array['key1']);
        $this->assertSame('value2', $subject->secret_array['key2']);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_array' => 'encrypted-secret-array-string-2',
        ]);

        $subject = $subject->fresh();

        $this->assertInstanceOf(ArrayObject::class, $subject->secret_array);
        $this->assertSame('value1', $subject->secret_array['key1']);
        $this->assertSame('value2', $subject->secret_array['key2']);

        $subject->secret_array = null;
        $subject->save();

        $this->assertNull($subject->secret_array);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret_array' => null,
        ]);

        $this->assertNull($subject->fresh()->secret_array);
    }

    public function testCustomEncrypterCanBeSpecified()
    {
        $customEncrypter = $this->mock(Encrypter::class);

        $this->assertNull(Model::$encrypter);

        Model::encryptUsing($customEncrypter);

        $this->assertSame($customEncrypter, Model::$encrypter);

        $this->encrypter->expects('encrypt')
            ->never();
        $this->encrypter->expects('decrypt')
            ->never();
        $customEncrypter->expects('encrypt')
            ->with('this is a secret string', false)
            ->andReturn('encrypted-secret-string');
        $customEncrypter->expects('decrypt')
            ->with('encrypted-secret-string', false)
            ->andReturn('this is a secret string');

        /** @var \Illuminate\Tests\Integration\Database\EncryptedCast $subject */
        $subject = EncryptedCast::create([
            'secret' => 'this is a secret string',
        ]);

        $this->assertSame('this is a secret string', $subject->secret);
        $this->assertDatabaseHas('encrypted_casts', [
            'id' => $subject->id,
            'secret' => 'encrypted-secret-string',
        ]);
    }
}

/**
 * @property $secret
 * @property $secret_array
 * @property $secret_json
 * @property $secret_object
 * @property $secret_collection
 */
class EncryptedCast extends Model
{
    public $timestamps = false;
    protected $guarded = [];

    public $casts = [
        'secret' => 'encrypted',
        'secret_array' => 'encrypted:array',
        'secret_json' => 'encrypted:json',
        'secret_object' => 'encrypted:object',
        'secret_collection' => 'encrypted:collection',
    ];
}
