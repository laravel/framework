<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\PhoneNumber;
use libphonenumber\PhoneNumberType;
use PHPUnit\Framework\TestCase;

class SupportPhoneTest extends TestCase
{
    public function test_can_instantiate_phone_number()
    {
        $phoneNumber = PhoneNumber::of('+201200954866');
        $this->assertInstanceOf(PhoneNumber::class, $phoneNumber);
        $this->assertEquals('+201200954866', $phoneNumber->getRawNumber());
    }

    public function test_can_get_country()
    {
        $phoneNumber = PhoneNumber::of('+201200954866');
        $this->assertEquals('EG', $phoneNumber->getCountry());
    }

    public function test_invalid_country_returns_null()
    {
        $phoneNumber = PhoneNumber::of('invalid-number');
        $this->assertNull($phoneNumber->getCountry());
    }

    // public function test_can_format_number()
    // {
    //     $phoneNumber = PhoneNumber::of('+201200954866');
    //     $this->assertEquals('+20 12 00954866', $phoneNumber->formatInternational());
    //     $this->assertEquals('012 00954866', $phoneNumber->formatNational());
    //     $this->assertEquals('+201200954866', $phoneNumber->formatE164());
    // }

    public function test_can_check_type()
    {
        $phoneNumber = PhoneNumber::of('+201200954866');
        $this->assertEquals(PhoneNumberType::MOBILE, $phoneNumber->getType(true));
    }

    public function test_can_validate_phone_number()
    {
        $phoneNumber = PhoneNumber::of('+201200954866');
        $this->assertTrue($phoneNumber->isValid());

        $invalidPhoneNumber = PhoneNumber::of('12345');
        $this->assertFalse($invalidPhoneNumber->isValid());
    }

    public function test_can_check_equality()
    {
        $phoneNumber1 = PhoneNumber::of('+201200954866');
        $phoneNumber2 = PhoneNumber::of('+201200954866');
        $phoneNumber3 = PhoneNumber::of('+14155552672');

        $this->assertTrue($phoneNumber1->equals($phoneNumber2));
        $this->assertTrue($phoneNumber1->notEquals($phoneNumber3));
    }

    public function test_can_format_for_country()
    {
        $phoneNumber = PhoneNumber::of('+441632960961');
        $this->assertEquals('01632 960961', $phoneNumber->formatForCountry('GB'));
    }

    public function test_can_convert_to_string()
    {
        $phoneNumber = PhoneNumber::of('+201200954866');
        $this->assertEquals('+201200954866', (string) $phoneNumber);
    }
}
