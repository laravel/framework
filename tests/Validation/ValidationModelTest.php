<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class ValidationModelTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        Carbon::setTestNow(null);
        m::close();
    }

    public function testModelRuleReturnsArrayCorrectly() {
        $instance = new EloquentModelWithRulesSet();

        $rules = $instance->getRules();

        $this->assertArrayHasKey('name', $rules);

        $this->assertArrayHasKey('email', $rules);

        $this->assertSame(['required','string'], $rules['name']);

        $this->assertSame(['required','email'], $rules['email']);
    }

    public function testModelRuleWithSetRulesArray() {
        $validator = Validator::make([
            'name' => 'My Name',
            'email' => 'test@example.com'
        ], Rule::model(EloquentModelWithRulesSet::class));

        $this->assertFalse($validator->fails());
    }

    public function testModelRuleWithoutSetRulesArray() {
        $validator = Validator::make([
            'name' => 'My Name',
            'email' => 'some-email'
        ], Rule::model(EloquentModelWithRulesSet::class));

        $this->assertTrue($validator->fails());

        $this->assertArrayHasKey('email', $validator->errors()->getMessages());

        $this->assertSame("The email must be a valid email address.", $validator->errors()->getMessages()['email'][0]);
    }

    public function testModelRuleWithNonExistingModel() {
        $this->expectException(ValidationException::class);

        $this->expectExceptionMessage("SomeNotExistingModel not found for Validation");

        Validator::make([
            'name' => 'My Name',
            'email' => 'some-email'
        ], [
            Rule::model('SomeNotExistingModel')
        ]);
    }
}

class EloquentModelWithRulesSet extends Model
{
    protected $fillable = [
        'name', 'email'
    ];

    protected $rules = [
        'name' => ['required', 'string'],
        'email' => ['required','email']
    ];
}