<?php

declare(strict_types=1);

namespace ConvertTypeWithStrictTypes;

use DateTime;
use Exception;
use PHPUnit\Framework\TestCase;

class ToDateAfterValidateTest extends TestCase
{
    public function test_it_should_cast_valid_date_string_to_datetime()
    {
        $value = '2025-09-21 12:30:00';

        $date = toDateOrNull($value);

        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('2025-09-21 12:30:00', $date->format('Y-m-d H:i:s'));
    }

    public function test_it_should_return_null_if_value_is_null()
    {
        $value = null;

        $date = toDateOrNull($value);

        $this->assertNull($date);
    }

    public function test_it_should_handle_date_without_time()
    {
        $value = '2025-09-21';

        $date = toDateOrNull($value);

        $this->assertInstanceOf(DateTime::class, $date);
        $this->assertSame('2025-09-21 00:00:00', $date->format('Y-m-d H:i:s'));
    }

    public function test_it_should_throw_exception_for_invalid_date_string()
    {
        $this->expectException(Exception::class);

        $value = 'not-a-valid-date';

        toDateOrNull($value);
    }
}
