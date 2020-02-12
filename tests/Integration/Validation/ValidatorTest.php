<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Validator;

class ValidatorTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name');
        });

        User::create(['first_name' => 'John']);
        User::create(['first_name' => 'John']);
    }

    public function testExists()
    {
        $validator = $this->getValidator(['first_name' => ['John', 'Jim']], ['first_name' => 'exists:users']);
        $this->assertFalse($validator->passes());
    }

    public function testImplicitAttributeFormatting()
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.string' => ':attribute must be a string!'], 'en');
        $validator = new Validator($translator, [['name' => 1]], ['*.name' => 'string']);

        $validator->setImplicitAttributesFormatter(function ($attribute) {
            [$line, $attribute] = explode('.', $attribute);

            return sprintf('%s at line %d', $attribute, $line + 1);
        });

        $validator->passes();

        $this->assertEquals('name at line 1 must be a string!', $validator->getMessageBag()->all()[0]);
    }

    protected function getValidator(array $data, array $rules)
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $validator = new Validator($translator, $data, $rules);
        $validator->setPresenceVerifier(new DatabasePresenceVerifier($this->app['db']));

        return $validator;
    }
}

class User extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
}
