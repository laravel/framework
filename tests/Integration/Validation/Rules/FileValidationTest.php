<?php

namespace Illuminate\Tests\Integration\Validation\Rules;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\File;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\TestWith;

class FileValidationTest extends TestCase
{
    #[TestWith(['0'])]
    #[TestWith(['.'])]
    #[TestWith(['*'])]
    #[TestWith(['__asterisk__'])]
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

    #[TestWith(['0'])]
    #[TestWith(['.'])]
    #[TestWith(['*'])]
    #[TestWith(['__asterisk__'])]
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

        $this->assertSame([
            0 => __('validation.mimetypes', ['attribute' => sprintf('files.%s', str_replace('_', ' ', $attribute)), 'values' => implode(', ', $mimes)]),
        ], $validator->messages()->all());
    }
}
