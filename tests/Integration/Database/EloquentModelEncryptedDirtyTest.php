<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;

class EloquentModelEncryptedDirtyTest extends TestCase
{
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
