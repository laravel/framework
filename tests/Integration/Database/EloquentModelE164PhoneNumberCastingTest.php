<?php

namespace Illuminate\Tests\Integration\Database;

use Exception;
use Illuminate\Database\Eloquent\Casts\AsE164PhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\PhoneNumber;
use Orchestra\Testbench\TestCase;

class EloquentModelE164PhoneNumberCastingTest extends TestCase
{
    public function test_mutates_to_e164_number()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone = '+32 12 34 56 78';
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone = PhoneNumber::of('+32 12/34.56.78');
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);
    }

    public function test_mutates_to_e164_number_with_implicit_country_field()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone_country = 'BE';
        $model->phone = '012 34 56 78';
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone_country = 'BE';
        $model->phone = PhoneNumber::of('+32 12/34.56.78');
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);
    }

    public function test_mutates_to_e164_number_with_explicit_country_field()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModelAndCountryField;
        $model->country = 'BE';
        $model->phone = '012 34 56 78';
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModelAndCountryField;
        $model->country = 'BE';
        $model->phone = PhoneNumber::of('+32 12/34.56.78');
        $this->assertEquals('+3212345678', $model->getAttributes()['phone']);
    }

    public function test_gets_phone_object()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->setRawAttributes(['phone' => '+3212345678']);
        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_throws_when_accessing_non_international_value()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel();
        $model->setRawAttributes(['phone' => '012 34 56 78']);
        $this->expectException(Exception::class);
        $model->phone;
    }

    public function test_serializes()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel();
        $model->phone = '+32 12 34 56 78';
        $this->assertEquals('+3212345678', $model->toArray()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModel();
        $model->phone = null;
        $this->assertEquals(null, $model->toArray()['phone']);
    }
}

class EloquentModelE164PhoneNumberCastingTestModel extends Model
{
    public $casts = [
        'phone' => AsE164PhoneNumber::class,
    ];
}

class EloquentModelE164PhoneNumberCastingTestModelAndCountryField extends Model
{
    protected function casts(): array
    {
        return [
            'phone' => AsE164PhoneNumber::of('country'),
        ];
    }
}
