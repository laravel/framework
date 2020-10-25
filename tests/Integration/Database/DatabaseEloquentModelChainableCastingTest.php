<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @group integration
 */
class DatabaseEloquentModelChainableCastingTest extends DatabaseTestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.key', 'base64:IUHRqAQ99pZ0A1MPjbuv1D6ff3jxv0GIvS2qIW4JNU4=');
    }

    public function testBasicChainedCast()
    {
        $model = new TestEloquentModelWithChainedCast;
        $model->password = 'password';

        $this->assertSame('password', $model->password);
        $this->assertSame('password', $model->getAttributes()['password']);
        $this->assertSame('password', $model->toArray()['password']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('password', $unserializedModel->password);
        $this->assertSame('password', $unserializedModel->getAttributes()['password']);
        $this->assertSame('password', $unserializedModel->toArray()['password']);

        $model->syncOriginal();
        $model->password = 'qwerty';
        $this->assertSame('password', $model->getOriginal('password'));

        $model = new TestEloquentModelWithChainedCast;
        $model->password = 'password';
        $model->syncOriginal();
        $model->password = 'qwerty';
        $model->getOriginal();

        $this->assertSame('qwerty', $model->password);

        $model = new TestEloquentModelWithChainedCast;
        $model->number = 12345;

        $this->assertSame(12345, $model->number);
        $this->assertSame(12345, $model->getAttributes()['number']);
        $this->assertSame(12345, $model->toArray()['number']);

        $model = new TestEloquentModelWithChainedCast;

        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $model->options = ['foo' => 'bar'];
        $model->options = ['foo' => 'bar'];
        $this->assertEquals(['foo' => 'bar'], $model->options);
        $this->assertEquals(['foo' => 'bar'], $model->options);

        $model = new TestEloquentModelWithChainedCast(['options' => []]);
        $model->syncOriginal();
        $model->options = ['foo' => 'bar'];
        $this->assertTrue($model->isDirty('options'));

        $model = new TestEloquentModelWithCustomCast;
        $model->birthday_at = now();
        $this->assertTrue(is_string($model->toArray()['birthday_at']));
    }

    public function testAdvancedChainedCast()
    {
        $model = new TestEloquentModelWithChainedCast;
        $model->hash = 'This is a HASH';

        $this->assertSame('hsah-a-si-siht', $model->hash);
        $this->assertSame('hsah-a-si-siht', $model->getAttributes()['hash']);
        $this->assertSame('hsah-a-si-siht', $model->toArray()['hash']);
    }
}

class TestEloquentModelWithChainedCast extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'password' => [
            'string',
            'encrypted',
        ],
        'number' => [
            'integer',
            'encrypted',
        ],
        'options' => [
            'array',
            'encrypted',
        ],
        'hash' => [
            ReverseCaster::class,
            SlugifyCaster::class,
            Base64Caster::class,
        ],
    ];
}

class ReverseCaster implements CastsInboundAttributes
{
    public function set($model, $key, $value, $attributes)
    {
        return [$key => strrev($value)];
    }
}

class SlugifyCaster implements CastsInboundAttributes
{
    public function set($model, $key, $value, $attributes)
    {
        return [$key => Str::slug($value)];
    }
}

class Base64Caster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return base64_decode($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => base64_encode($value)];
    }
}
