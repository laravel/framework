<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ValidatorTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('first_name');
        });

        User::create(['uuid' => (string) Str::uuid(), 'first_name' => 'John']);
        User::create(['uuid' => (string) Str::uuid(), 'first_name' => 'Jim']);
    }

    public function testExists(): void
    {
        $validator = $this->getValidator(['first_name' => ['John', 'Taylor']], ['first_name' => 'exists:users']);
        $this->assertFalse($validator->passes());
    }

    public function testUnique(): void
    {
        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:'.User::class]);
        $this->assertFalse($validator->passes());

        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:'.User::class.',first_name,1']);
        $this->assertTrue($validator->passes());

        $validator = $this->getValidator(['first_name' => 'Taylor'], ['first_name' => 'unique:'.User::class]);
        $this->assertTrue($validator->passes());
    }

    public function testExistsAndUniqueCompareJsonPathValuesAsStrings(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->json('meta');
        });

        Profile::create(['meta' => ['external_id' => 7, 'code' => '007']]);

        foreach ([7, '7', [7], ['7']] as $value) {
            $validator = $this->getValidator(['value' => $value], ['value' => 'exists:profiles,meta->external_id']);
            $this->assertTrue($validator->passes());
        }

        $validator = $this->getValidator(['value' => [7, 8]], ['value' => 'exists:profiles,meta->external_id']);
        $this->assertFalse($validator->passes());

        $validator = $this->getValidator(['value' => 7], ['value' => 'exists:profiles,meta->code']);
        $this->assertFalse($validator->passes());

        $validator = $this->getValidator(['value' => 7], ['value' => 'unique:profiles,meta->external_id']);
        $this->assertFalse($validator->passes());

        $validator = $this->getValidator(['value' => 8], ['value' => 'unique:profiles,meta->external_id']);
        $this->assertTrue($validator->passes());

        Profile::create(['meta' => ['external_id' => '7']]);

        $validator = $this->getValidator(['value' => [7, 8]], ['value' => 'exists:profiles,meta->external_id']);
        $this->assertFalse($validator->passes());
    }

    public function testExistsAndUniqueCompareJsonPathExtraConditionsAsStrings(): void
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email');
            $table->json('meta');
        });

        Profile::create([
            'email' => 'taylor@laravel.com',
            'meta' => ['external_id' => 7, 'code' => '007', 'active' => true],
        ]);

        $data = ['email' => 'taylor@laravel.com'];

        $this->assertTrue($this->getValidator($data, ['email' => 'exists:profiles,email,meta->external_id,7'])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => 'exists:profiles,email,meta->external_id,!7'])->passes());
        $this->assertTrue($this->getValidator($data, ['email' => 'exists:profiles,email,meta->external_id,!8'])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => 'exists:profiles,email,meta->code,7'])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => 'unique:profiles,email,NULL,id,meta->external_id,7'])->passes());
        $this->assertTrue($this->getValidator($data, ['email' => 'unique:profiles,email,7,meta->external_id'])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => 'unique:profiles,email,8,meta->external_id'])->passes());
        $this->assertTrue($this->getValidator($data, ['email' => [Rule::exists('profiles', 'email')->where('meta->external_id', 7)]])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => [Rule::exists('profiles', 'email')->whereNot('meta->external_id', 7)]])->passes());
        $this->assertTrue($this->getValidator($data, ['email' => [Rule::exists('profiles', 'email')->where('meta->active', true)]])->passes());
        $this->assertFalse($this->getValidator($data, ['email' => [Rule::exists('profiles', 'email')->where('meta->active', false)]])->passes());
    }

    public function testUniqueWithCustomModelKey(): void
    {
        $_SERVER['CUSTOM_MODEL_KEY_NAME'] = 'uuid';

        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:'.UserWithUuid::class]);
        $this->assertFalse($validator->passes());

        $user = UserWithUuid::where('first_name', 'John')->first();

        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:'.UserWithUuid::class.',first_name,'.$user->uuid]);
        $this->assertTrue($validator->passes());

        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:users,first_name,'.$user->uuid.',uuid']);
        $this->assertTrue($validator->passes());

        $validator = $this->getValidator(['first_name' => 'John'], ['first_name' => 'unique:users,first_name,'.$user->uuid.',id']);
        $this->assertFalse($validator->passes());

        $validator = $this->getValidator(['first_name' => 'Taylor'], ['first_name' => 'unique:'.UserWithUuid::class]);
        $this->assertTrue($validator->passes());

        unset($_SERVER['CUSTOM_MODEL_KEY_NAME']);
    }

    public function testImplicitAttributeFormatting(): void
    {
        $translator = new Translator(new ArrayLoader, 'en');
        $translator->addLines(['validation.string' => ':attribute must be a string!'], 'en');
        $validator = new Validator($translator, [['name' => 1]], ['*.name' => 'string']);

        $validator->setImplicitAttributesFormatter(function ($attribute) {
            [$line, $attribute] = explode('.', $attribute);

            return sprintf('%s at line %d', $attribute, $line + 1);
        });

        $validator->passes();

        $this->assertSame('name at line 1 must be a string!', $validator->getMessageBag()->all()[0]);
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
    protected $guarded = [];
}

class UserWithUuid extends Model
{
    protected $table = 'users';
    public $timestamps = false;
    protected $guarded = [];
    protected $keyType = 'string';
    public $incrementing = false;

    public function getKeyName()
    {
        return 'uuid';
    }
}

class Profile extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['meta' => 'array'];
}
