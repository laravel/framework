<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;

use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\TestCase;

class PhoneNumberTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_valid_phone_number(): void
    {
        $rules = [
            "us_phone" => 'phone:US',
            "bj_phone" => 'phone:BJ',
            "one_of_phone" => 'phone:BJ,US,NE',
        ];

        $data = [
            'us_phone' => "123-456-7890",
            'bj_phone' => "+229 00000000",
            'one_of_phone' => "+227 00000000",
        ];
        $passes = Validator::make($data, $rules)->passes();
        $this->assertTrue($passes);
    }

    public function test_invalid_phone_number(): void
    {
        $rules = [
            "us_phone" => 'phone:US',
            "bj_phone" => 'phone:BJ',
            "one_of_phone" => 'phone:BJ,US,NE',
        ];

        $data = [
            'us_phone' => "123456789",
        ];
        $passes = Validator::make($data, $rules)->passes();
        $this->assertFalse($passes);
    }
}
