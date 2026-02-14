<?php

namespace Illuminate\Tests\Integration\Validation;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

class ValidatesRequestsStatusTest extends TestCase
{
    use ValidatesRequests;

    public function testValidateAllowsCustomStatus(): void
    {
        $request = Request::create('/', 'GET', ['name' => null]);

        try {
            $this->validate($request, ['name' => 'string'], [], [], 400);
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $e) {
            $this->assertSame(400, $e->status);
        }
    }

    public function testValidateWithBagAllowsCustomStatus(): void
    {
        $request = Request::create('/', 'GET', ['name' => null]);

        try {
            $this->validateWithBag('custom-bag', $request, ['name' => 'string'], [], [], 409);
            $this->fail('Expected validation to fail.');
        } catch (ValidationException $e) {
            $this->assertSame('custom-bag', $e->errorBag);
            $this->assertSame(409, $e->status);
        }
    }
}
