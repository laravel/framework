<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Support\Facades\Validator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class DateFormatValidationTest extends TestCase
{
    #[TestWith(['UTC'])]
    #[TestWith(['Europe/Amsterdam'])]
    public function test_it_can_validate_regardless_of_timezone(string $timezone): void
    {
        date_default_timezone_set($timezone);

        $payload = ['date' => '2025-03-30 02:00:00'];
        $rules = ['date' => 'date_format:Y-m-d H:i:s'];

        $validator = Validator::make($payload, $rules);

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }
}
