<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Validation\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Translation\Translator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class ValidatorTest extends DatabaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function ($table) {
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
