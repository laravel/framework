<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Orchestra\Testbench\TestCase;

class FileValidationTest extends TestCase
{
    /**
     * @dataProvider attributeProvider
     */
    public function test_it_can_validate_attribute_as_array(string $attribute)
    {
        $file = UploadedFile::fake()->create('laravel.png', 1, 'image/png');

        $validator = Validator::make([
            'files' => [
                $attribute => $file,
            ],
        ], [
            'files.*' => ['required', File::types(['image/png', 'image/jpeg'])],
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider attributeProvider
     */
    public function test_it_can_validate_attribute_as_array_when_validation_should_fails(string $attribute)
    {
        $file = UploadedFile::fake()->create('laravel.php', 1, 'image/php');

        $validator = Validator::make([
            'files' => [
                $attribute => $file,
            ],
        ], [
            'files.*' => ['required', File::types($mimes = ['image/png', 'image/jpeg'])],
        ]);

        $this->assertFalse($validator->passes());

        $messages = $validator->messages()->all();
        $this->assertCount(1, $messages);
        // The message should contain the proper attribute name
        $this->assertStringContainsString('files', $messages[0]);
    }

    public static function attributeProvider()
    {
        return [
            ['0'],
            ['.'],
            ['*'],
            ['__asterisk__'],
        ];
    }
}
