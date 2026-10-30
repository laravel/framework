<?php

namespace Illuminate\Tests\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\ValidatedInput;
use Illuminate\Tests\Support\Concerns\TestsInteractsWithDataContract;
use PHPUnit\Framework\TestCase;

class ValidatedInputTest extends TestCase
{
    use TestsInteractsWithDataContract;

    public function test_can_access_input()
    {
        $input = new ValidatedInput(['name' => 'Taylor', 'votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->all(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_can_merge_items()
    {
        $input = new ValidatedInput(['name' => 'Taylor']);

        $input = $input->merge(['votes' => 100]);

        $this->assertSame('Taylor', $input->name);
        $this->assertSame('Taylor', $input['name']);
        $this->assertEquals(['name' => 'Taylor'], $input->only(['name']));
        $this->assertEquals(['name' => 'Taylor'], $input->except(['votes']));
        $this->assertEquals(['name' => 'Taylor', 'votes' => 100], $input->all());
    }

    public function test_exists_method()
    {
        $this->assertDataExistsContract();
        $this->assertDataExistsNestedContract();
    }

    public function test_has_method()
    {
        $this->assertDataHasContract();
        $this->assertDataHasNestedContract();
    }

    public function test_has_any_method()
    {
        $this->assertDataHasAnyContract();
        $this->assertDataHasAnyNestedContract();
    }

    public function test_when_has_method()
    {
        $this->assertDataWhenHasContract();
        $this->assertDataWhenHasNestedContract();
    }

    public function test_filled_method()
    {
        $this->assertDataFilledContract();
        $this->assertDataFilledNestedContract();
    }

    public function test_is_not_filled_method()
    {
        $this->assertDataIsNotFilledContract();
        $this->assertDataIsNotFilledNestedContract();
    }

    public function test_any_filled_method()
    {
        $this->assertDataAnyFilledContract();
        $this->assertDataAnyFilledNestedContract();
    }

    public function test_when_filled_method()
    {
        $this->assertDataWhenFilledContract();
        $this->assertDataWhenFilledNestedContract();
    }

    public function test_when_enum_method()
    {
        $this->assertDataWhenEnumContract();
    }

    public function test_missing_method()
    {
        $this->assertDataMissingContract();
        $this->assertDataMissingNestedContract();
    }

    public function test_when_missing_method()
    {
        $this->assertDataWhenMissingContract();
        $this->assertDataWhenMissingNestedContract();
    }

    public function test_keys_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name', 'surname', 'foo'], $input->keys());
    }

    public function test_all_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertEquals(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']], $input->all());
    }

    public function test_input_method()
    {
        $input = new ValidatedInput(['name' => 'Fatih', 'surname' => 'AYDIN', 'foo' => ['bar' => null, 'baz' => '']]);

        $this->assertSame('Fatih', $input->input('name'));
        $this->assertNull($input->input('foo.bar'));
        $this->assertSame('test', $input->input('foo.bat', 'test'));
    }

    public function test_str_method()
    {
        $this->assertDataStrContract();
    }

    public function test_string_method()
    {
        $this->assertDataStringContract();
    }

    public function test_boolean_method()
    {
        $this->assertDataBooleanContract();
    }

    public function test_integer_method()
    {
        $this->assertDataIntegerContract();
    }

    public function test_float_method()
    {
        $this->assertDataFloatContract();
    }

    public function test_clamp_method()
    {
        $this->assertDataClampContract();
    }

    public function test_date_method()
    {
        $this->assertDataDateContract();
    }

    public function test_interval_method()
    {
        $this->assertDataIntervalContract();
    }

    public function test_enum_method()
    {
        $this->assertDataEnumContract();
        $this->assertDataEnumNestedContract();
    }

    public function test_enums_method()
    {
        $this->assertDataEnumsContract();
    }

    public function test_array_method()
    {
        $this->assertDataArrayContract();
        $this->assertDataArrayArrayableContract();
    }

    public function test_file_method()
    {
        $file = UploadedFile::fake()->create('document.pdf');

        $input = new ValidatedInput([
            'name' => 'Taylor',
            'avatar' => $file,
        ]);

        $this->assertInstanceOf(UploadedFile::class, $input->file('avatar'));
        $this->assertSame($file, $input->file('avatar'));
        $this->assertNull($input->file('name'));
        $this->assertNull($input->file('missing'));
        $this->assertSame('default', $input->file('missing', 'default'));
        $this->assertSame('default', $input->file('name', 'default'));
    }

    public function test_collect_method()
    {
        $this->assertDataCollectContract();
        $this->assertDataCollectNestedContract();
    }

    public function test_only_method()
    {
        $this->assertDataOnlyContract();
        $this->assertDataOnlyNestedContract();
    }

    public function test_except_method()
    {
        $this->assertDataExceptContract();
        $this->assertDataExceptNestedContract();
    }

    public function test_null_values()
    {
        $this->assertDataNullValuesContract();
        $this->assertDataNullValuesIgnoreDefaultsContract();
        $this->assertDataNestedNullValuesContract();
    }

    protected static function arrayToDataFactory(array $data)
    {
        return new ValidatedInput($data);
    }
}
