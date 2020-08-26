<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Validation\ValidationException;

class DatabaseEloquentConcernsValidatesAttributesTraitTest extends DatabaseTestCase
{
    public function testValidate()
    {
        /**
         * Test validation error.
         */
        $model = new EloquentModelWithValidatesAttributesStub([
            'foo' => null,
            'bar' => 'abc',
            'baz' => 'abc',
        ]);

        try {
            $model->validate();

            $this->assertFalse(true);
        } catch (ValidationException $e) {
            $messages = $e->validator->errors()->toArray();

            $this->assertArrayHasKey('foo', $messages);
            $this->assertCount(1, $messages['foo']);
            $this->assertEquals('The foo attribute is required.', $messages['foo'][0]);

            $this->assertArrayHasKey('bar', $messages);
            $this->assertCount(1, $messages['bar']);
            $this->assertEquals('The bar attribute must be an integer.', $messages['bar'][0]);

            $this->assertArrayHasKey('baz', $messages);
            $this->assertCount(1, $messages['baz']);
            $this->assertEquals('The baz attribute must be an integer.', $messages['baz'][0]);
        }

        /**
         * Test error after update.
         */
        $model->foo = 123;

        try {
            $model->validate();
        } catch (ValidationException $e) {
            $messages = $e->validator->errors()->toArray();

            $this->assertArrayHasKey('foo', $messages);
            $this->assertCount(1, $messages['foo']);
            $this->assertEquals('The foo attribute must be a string.', $messages['foo'][0]);

            $this->assertArrayHasKey('bar', $messages);
            $this->assertCount(1, $messages['bar']);
            $this->assertEquals('The bar attribute must be an integer.', $messages['bar'][0]);

            $this->assertArrayHasKey('baz', $messages);
            $this->assertCount(1, $messages['baz']);
            $this->assertEquals('The baz attribute must be an integer.', $messages['baz'][0]);
        }

        /**
         * Test success.
         */
        $model->foo = 'abc';
        $model->bar = 123;
        $model->baz = 123;

        $validated = $model->validate();

        $this->assertIsArray($validated);
        $this->assertCount(3, $validated);

        $this->assertArrayHasKey('foo', $validated);
        $this->assertEquals($model->foo, $validated['foo']);

        $this->assertArrayHasKey('bar', $validated);
        $this->assertEquals($model->bar, $validated['bar']);

        $this->assertArrayHasKey('baz', $validated);
        $this->assertEquals($model->baz, $validated['baz']);
    }

    public function testValidateOnSaving()
    {
        try {
            EloquentModelWithValidatesAttributesStub::create([
                'foo' => 123,
                'bar' => 'abc',
                'baz' => 'abc',
            ]);
        } catch (ValidationException $e) {
            $messages = $e->validator->errors()->toArray();
            $this->assertEquals(3, count($messages));
        }

        try {
            $model = EloquentModelWithValidatesAttributesStub::make([
                'foo' => 123,
                'bar' => 'abc',
                'baz' => 'abc',
            ]);

            $model->save();
        } catch (ValidationException $e) {
            $messages = $e->validator->errors()->toArray();
            $this->assertCount(3, $messages);
        }

        $model = EloquentModelWithValidatesAttributesStub::make([
            'foo' => 'abc',
            'bar' => 123,
            'baz' => 123,
        ]);

        $model->save();

        $this->assertTrue($model->exists);
    }
}

class EloquentModelWithValidatesAttributesStub extends Model
{
    public $connection;
    protected $table = 'stub';

    protected $validationRules = [
        'foo' => 'required|string',
        'bar' => 'required|integer',
        'baz' => 'nullable|integer',
    ];

    protected $validationMessages = [
        'foo.required' => 'The foo attribute is required.',
        'foo.string' => 'The foo attribute must be a string.',
        'bar.required' => 'The bar attribute is required.',
        'bar.integer' => 'The bar attribute must be an integer.',
        'baz.integer' => 'The baz attribute must be an integer.',
    ];

    protected $guarded = [];

    public function disableValidatesOnSaving()
    {
        $this->validateOnSaving = false;
    }

    public function save(array $options = [])
    {
        if ($this->fireModelEvent('saving') === false) {
            return false;
        }

        $this->exists = true;

        $this->fireModelEvent('saved', false);
    }
}
