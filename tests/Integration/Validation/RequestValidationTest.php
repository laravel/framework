<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class RequestValidationTest extends TestCase
{
    public function testValidateMacro()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validate(['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateMacroWhenItFails()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/', 'GET', ['name' => null]);

        $request->validate(['name' => 'string']);
    }

    public function testValidateWithBagMacro()
    {
        $request = Request::create('/', 'GET', ['name' => 'Taylor']);

        $validated = $request->validateWithBag('some_bag', ['name' => 'string']);

        $this->assertSame(['name' => 'Taylor'], $validated);
    }

    public function testValidateWithBagMacroWhenItFails()
    {
        $request = Request::create('/', 'GET', ['name' => null]);

        try {
            $request->validateWithBag('some_bag', ['name' => 'string']);
        } catch (ValidationException $validationException) {
            $this->assertSame('some_bag', $validationException->errorBag);
        }
    }

    public function testValidateWithMacro()
    {
        $input = [
            'age' => 23,
            'height' => 175,
            'weight' => 62,
        ];

        $request = Request::create('/', 'GET', $input);

        $validated = $request->validateWith('numeric', ['age', 'height', 'weight']);

        $this->assertSame($input, $validated);
    }

    public function testValidateWithMacroWhenItFails()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/', 'GET', [
            'first_name' => 'John2',
            'last_name' => 'Doe^',
        ]);

        $request->validateWith('alpha', ['first_name', 'last_name']);
    }
}
