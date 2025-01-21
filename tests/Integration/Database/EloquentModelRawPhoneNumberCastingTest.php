<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Casts\AsRawPhoneNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\PhoneNumber;
use Orchestra\Testbench\TestCase;

class EloquentModelRawPhoneNumberCastingTest extends TestCase
{
    public function test_mutates_to_raw_number()
    {
        $model = new ModelWithRawCast;
        $model->phone = '01200954866';
        $this->assertEquals('01200954866', $model->getAttributes()['phone']);

        $model = new ModelWithRawCast;
        $model->phone = PhoneNumber::of('01200954866');
        $this->assertEquals('01200954866', $model->getAttributes()['phone']);

        $model = new ModelWithRawCast;
        $model->phone = PhoneNumber::of('01200954866', 'EG');
        $this->assertEquals('01200954866', $model->getAttributes()['phone']);

        $model = new ModelWithRawCast;
        $model->phone = PhoneNumber::of('012-34-56-78', 'US');
        $this->assertEquals('012-34-56-78', $model->getAttributes()['phone']);
    }

    public function test_gets_phone_object()
    {
        $model = new ModelWithRawCast;
        $model->setRawAttributes(['phone' => '01200954866']);
        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_gets_with_implicit_country_field()
    {
        $model = new ModelWithIncompleteRawCast;
        $model->setRawAttributes([
            'phone_country' => 'EG',
            'phone' => '01200954866',
        ]);
        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_gets_with_explicit_country_field()
    {
        $model = new ModelWithRawCastAndCountryField;
        $model->setRawAttributes([
            'country' => 'EG',
            'phone' => '01200954866',
        ]);
        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_gets_phone_object_when_accessing_incomplete_raw_cast_with_international_number()
    {
        $model = new ModelWithIncompleteRawCast;
        $model->setRawAttributes(['phone' => '+201200954866']);

        $this->assertIsObject($model->phone);
        $this->assertInstanceOf(PhoneNumber::class, $model->phone);
    }

    public function test_serializes()
    {
        $model = new ModelWithRawCast;
        $model->phone = '01200954866';
        $this->assertEquals('01200954866', $model->toArray()['phone']);

        $model = new ModelWithRawCast;
        $model->phone = null;
        $this->assertEquals(null, $model->toArray()['phone']);
    }
}

class ModelWithRawCast extends Model
{
    protected $casts = [
        'phone' => AsRawPhoneNumber::class.':EG',
    ];
}

class ModelWithRawCastAndCountryField extends Model
{
    protected $casts = [
        'phone' => AsRawPhoneNumber::class.':country',
    ];
}

class ModelWithIncompleteRawCast extends Model
{
    protected $casts = [
        'phone' => AsRawPhoneNumber::class,
    ];
}
