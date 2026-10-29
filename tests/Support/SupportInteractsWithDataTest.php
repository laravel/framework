<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Traits\InteractsWithData;
use Illuminate\Tests\Support\Concerns\TestsInteractsWithDataContract;
use PHPUnit\Framework\TestCase;

class SupportInteractsWithDataTest extends TestCase
{
    use TestsInteractsWithDataContract;

    public function testExistsMethod()
    {
        $this->assertDataExistsContract();
        $this->assertDataExistsNestedContract();
    }

    public function testHasMethod()
    {
        $this->assertDataHasContract();
        $this->assertDataHasNestedContract();
    }

    public function testHasAnyMethod()
    {
        $this->assertDataHasAnyContract();
        $this->assertDataHasAnyNestedContract();
    }

    public function testWhenHasMethod()
    {
        $this->assertDataWhenHasContract();
        $this->assertDataWhenHasNestedContract();
    }

    public function testFilledMethod()
    {
        $this->assertDataFilledContract();
        $this->assertDataFilledNestedContract();
    }

    public function testIsNotFilledMethod()
    {
        $this->assertDataIsNotFilledContract();
        $this->assertDataIsNotFilledNestedContract();
    }

    public function testAnyFilledMethod()
    {
        $this->assertDataAnyFilledContract();
        $this->assertDataAnyFilledNestedContract();
    }

    public function testWhenFilledMethod()
    {
        $this->assertDataWhenFilledContract();
        $this->assertDataWhenFilledNestedContract();
    }

    public function testWhenEnumMethod()
    {
        $this->assertDataWhenEnumContract();
    }

    public function testMissingMethod()
    {
        $this->assertDataMissingContract();
        $this->assertDataMissingNestedContract();
    }

    public function testWhenMissingMethod()
    {
        $this->assertDataWhenMissingContract();
        $this->assertDataWhenMissingNestedContract();
    }

    public function testStrMethod()
    {
        $this->assertDataStrContract();
    }

    public function testStringMethod()
    {
        $this->assertDataStringContract();
    }

    public function testBooleanMethod()
    {
        $this->assertDataBooleanContract();
    }

    public function testIntegerMethod()
    {
        $this->assertDataIntegerContract();
    }

    public function testFloatMethod()
    {
        $this->assertDataFloatContract();
    }

    public function testClampMethod()
    {
        $this->assertDataClampContract();
    }

    public function testDateMethod()
    {
        $this->assertDataDateContract();
    }

    public function testIntervalMethod()
    {
        $this->assertDataIntervalContract();
    }

    public function testEnumMethod()
    {
        $this->assertDataEnumContract();
        $this->assertDataEnumNestedContract();
    }

    public function testEnumsMethod()
    {
        $this->assertDataEnumsContract();
    }

    public function testArrayMethod()
    {
        $this->assertDataArrayContract();
        $this->assertDataArrayArrayableContract();
    }

    public function testCollectMethod()
    {
        $this->assertDataCollectContract();
        $this->assertDataCollectNestedContract();
    }

    public function testOnlyMethod()
    {
        $this->assertDataOnlyContract();
        $this->assertDataOnlyNestedContract();
    }

    public function testExceptMethod()
    {
        $this->assertDataExceptContract();
        $this->assertDataExceptNestedContract();
    }

    public function testNullValues()
    {
        $this->assertDataNullValuesContract();
        $this->assertDataNullValuesIgnoreDefaultsContract();
        $this->assertDataNestedNullValuesContract();
    }

    protected static function arrayToDataFactory(array $data)
    {
        return new class($data)
        {
            use InteractsWithData;

            public function __construct(protected array $attributes)
            {
            }

            public function all($keys = null)
            {
                if (! $keys) {
                    return $this->attributes;
                }

                $results = [];

                foreach (is_array($keys) ? $keys : func_get_args() as $key) {
                    data_set($results, $key, data_get($this->attributes, $key));
                }

                return $results;
            }

            protected function data($key = null, $default = null)
            {
                return data_get($this->attributes, $key, $default);
            }
        };
    }
}
