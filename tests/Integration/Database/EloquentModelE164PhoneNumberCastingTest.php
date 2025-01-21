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
        $model->phone = '+201200954866';
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone = PhoneNumber::of('+201200954866');
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);
    }

    public function test_mutates_to_e164_number_with_implicit_country_field()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone_country = 'EG';
        $model->phone = '01200954866';
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->phone_country = 'EG';
        $model->phone = PhoneNumber::of('+201200954866');
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);
    }

    public function test_mutates_to_e164_number_with_explicit_country_field()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModelAndCountryField;
        $model->country = 'EG';
        $model->phone = '01200954866';
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);

        $model = new EloquentModelE164PhoneNumberCastingTestModelAndCountryField;
        $model->country = 'EG';
        $model->phone = PhoneNumber::of('+201200954866');
        $this->assertEquals('+201200954866', $model->getAttributes()['phone']);
    }

    public function test_gets_phone_object()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel;
        $model->setRawAttributes(['phone' => '+201200954866']);
        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_throws_when_accessing_non_international_value()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel();
        $model->setRawAttributes(['phone' => '01200954866']);
        $this->expectException(Exception::class);
        $model->phone;
    }

    public function test_serializes()
    {
        $model = new EloquentModelE164PhoneNumberCastingTestModel();
        $model->phone = '+201200954866';
        $this->assertEquals('+201200954866', $model->toArray()['phone']);

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
