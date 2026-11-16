<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile as SymfonyUploadedFile;

class RequestValidationTest extends TestCase
{
    public function testValidateMacro(): void
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validate(['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateMacroWhenItFails(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/', 'GET', ['name' => null]);

        $request->validate(['name' => 'string']);
    }

    public function testValidateWithBagMacro(): void
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validateWithBag('some_bag', ['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateWithBagMacroWhenItFails(): void
    {
        $request = Request::create('/', 'GET', ['name' => null]);

        try {
            $request->validateWithBag('some_bag', ['name' => 'string']);
        } catch (ValidationException $validationException) {
            $this->assertSame('some_bag', $validationException->errorBag);
        }
    }

    public function testValidateFileWithEmptyTextFieldDoesNotBypassValidation(): void
    {
        // This is the security fix for SECO-675: an empty text field should not
        // shadow an uploaded file, allowing it to bypass validation rules like
        // 'nullable', 'image', 'mimes', 'max', etc.
        $file = new SymfonyUploadedFile(__FILE__, 'avatar.jpg');

        $request = Request::create('/', 'POST', ['avatar' => ''], [], ['avatar' => $file]);

        // The validation data should contain the file, not the empty string,
        // so the 'image' rule will properly validate it
        $validationData = $request->all();

        $this->assertInstanceOf(UploadedFile::class, $validationData['avatar']);

        // Now let's verify that validation works correctly
        $this->expectException(ValidationException::class);

        // This should fail because the file is not a valid image
        $request->validate(['avatar' => 'required|image']);
    }

    public function testValidateNullableFileWithEmptyTextFieldAllowsFile(): void
    {
        $file = new SymfonyUploadedFile(__FILE__, 'document.pdf');

        $request = Request::create('/', 'POST', ['document' => ''], [], ['document' => $file]);

        // With an empty text field, the file should not be shadowed
        $validationData = $request->all();
        $this->assertInstanceOf(UploadedFile::class, $validationData['document']);

        // Validation should check the actual file, not the empty string
        try {
            $request->validate(['document' => 'nullable|image']);
            $this->fail('Expected ValidationException for file that is not an image');
        } catch (ValidationException $e) {
            // The document file should fail the 'image' rule
            $this->assertArrayHasKey('document', $e->errors());
        }
    }

    public function testValidateNullableFileWithNoInputAllowsFile(): void
    {
        $file = new SymfonyUploadedFile(__FILE__, 'avatar.jpg');

        $request = Request::create('/', 'POST', [], [], ['avatar' => $file]);

        $validationData = $request->all();
        $this->assertInstanceOf(UploadedFile::class, $validationData['avatar']);
    }

    public function testValidatePreservesNonEmptyInputOverFile(): void
    {
        $file = new SymfonyUploadedFile(__FILE__, 'avatar.jpg');

        $request = Request::create('/', 'POST', ['field' => 'text-value'], [], ['field' => $file]);

        $validationData = $request->all();

        // Non-empty text input should still win over files
        $this->assertSame('text-value', $validationData['field']);
        // But the file should still be accessible via file()
        $this->assertInstanceOf(UploadedFile::class, $request->file('field'));
    }
}
