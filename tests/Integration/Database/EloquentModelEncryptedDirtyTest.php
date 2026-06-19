<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Orchestra\Testbench\TestCase;

class EloquentModelEncryptedDirtyTest extends TestCase
{
    public function testEncryptedJsonIsNotDirtyWhenStoredFormattingDiffersFromReEncoded()
    {
        config(['app.key' => str_repeat('a', 32)]);
        Model::$encrypter = null;

        $model = new EncryptedDirtyAttributeCast;

        // Simulate a row whose encrypted JSON was stored with non-canonical
        // whitespace (e.g. migrated from another system). Decrypting yields the
        // same structure Laravel re-encodes, so the attribute is not dirty.
        $model->setRawAttributes([
            'secret_array' => Crypt::encryptString('{"a": 1, "b": 2}'),
        ], true);

        $model->secret_array = ['a' => 1, 'b' => 2];

        $this->assertFalse($model->isDirty('secret_array'));
    }

    public function testDirtyAttributeBehaviorWithNoPreviousKeys()
    {
        config(['app.key' => str_repeat('a', 32)]);
        Model::$encrypter = null;

        $model = new EncryptedDirtyAttributeCast([
            'secret' => 'some-secret',
            'secret_array_object' => [1, 2, 3],
        ]);

        $model->syncOriginal();

        $this->assertFalse($model->isDirty('secret'));
        $this->assertFalse($model->isDirty('secret_array_object'));

        $model->secret = 'some-secret';
        $model->secret_array_object = [1, 2, 3];

        // Encrypted attributes should always be considered dirty if updated in any way because of rotatable encryption keys...
        $this->assertFalse($model->isDirty('secret'));
        $this->assertFalse($model->isDirty('secret_array_object'));

        $model->secret = 'some-other-secret';
        $model->secret_array_object = [4, 5, 6];

        // Encrypted attributes should always be considered dirty if updated in any way because of rotatable encryption keys...
        $this->assertTrue($model->isDirty('secret'));
        $this->assertTrue($model->isDirty('secret_array_object'));
    }

    public function testIsEncryptedCastableOverrideIsHonoredOnTheCastPath()
    {
        config(['app.key' => str_repeat('a', 32)]);
        Model::$encrypter = null;

        // The attribute is declared as a plain "array" cast, but the model
        // overrides isEncryptedCastable() to treat it as encrypted. The read,
        // write, and dirty-comparison paths must all honor that override.
        $model = new OverriddenEncryptedCastable;

        $model->data = ['a' => 1, 'b' => 2];

        // The stored value must be encrypted (not plain JSON)...
        $stored = $model->getAttributes()['data'];
        $this->assertNotSame('{"a":1,"b":2}', $stored);
        $this->assertSame('{"a":1,"b":2}', Model::currentEncrypter()->decrypt($stored, false));

        // ...and reading must decrypt then JSON-decode it back.
        $this->assertSame(['a' => 1, 'b' => 2], $model->data);

        // Dirty comparison runs through the encrypted comparator (always dirty
        // on any re-assignment because of rotatable keys), not the plain JSON one.
        $model->syncOriginal();
        $model->data = ['a' => 1, 'b' => 2];
        $this->assertFalse($model->isDirty('data'));
    }

    public function testDirtyAttributeBehaviorWithPreviousKeys()
    {
        config(['app.key' => str_repeat('a', 32)]);
        config(['app.previous_keys' => [str_repeat('b', 32)]]);
        Model::$encrypter = null;

        $model = new EncryptedDirtyAttributeCast([
            'secret' => 'some-secret',
            'secret_array_object' => [1, 2, 3],
        ]);

        $model->syncOriginal();

        $this->assertFalse($model->isDirty('secret'));
        $this->assertFalse($model->isDirty('secret_array_object'));

        $model->secret = 'some-secret';
        $model->secret_array_object = [1, 2, 3];

        // Encrypted attributes should always be considered dirty if updated in any way because of rotatable encryption keys...
        $this->assertTrue($model->isDirty('secret'));
        $this->assertTrue($model->isDirty('secret_array_object'));

        $model->secret = 'some-other-secret';
        $model->secret_array_object = [4, 5, 6];

        // Encrypted attributes should always be considered dirty if updated in any way because of rotatable encryption keys...
        $this->assertTrue($model->isDirty('secret'));
        $this->assertTrue($model->isDirty('secret_array_object'));
    }
}

/**
 * @property $secret
 * @property $secret_array
 * @property $secret_json
 * @property $secret_object
 * @property $secret_collection
 */
class EncryptedDirtyAttributeCast extends Model
{
    protected $guarded = [];

    public $casts = [
        'secret' => 'encrypted',
        'secret_array' => 'encrypted:array',
        'secret_json' => 'encrypted:json',
        'secret_object' => 'encrypted:object',
        'secret_collection' => 'encrypted:collection',
        'secret_array_object' => AsEncryptedArrayObject::class,
    ];
}

class OverriddenEncryptedCastable extends Model
{
    protected $guarded = [];

    public $casts = [
        'data' => 'array',
    ];

    protected function isEncryptedCastable($key)
    {
        return $key === 'data' || parent::isEncryptedCastable($key);
    }
}
