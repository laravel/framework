<?php

namespace Illuminate\Tests\Integration\Foundation\Testing\Concerns;

use LogicException;
use Orchestra\Testbench\TestCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

class MakesHttpRequestsTest extends TestCase
{
    public function testAnExceptionIsThrownIfATestTriesToMakeMoreThanOneRequest()
    {
        Route::get('test', function () {
            return 'Hello world!';
        });

        $this->get('test')
            ->assertStatus(200)
            ->assertSee('Hello world!');

        try {
            $this->get('test');
            $this->fail(sprintf('A %s exception should have been thrown.', LogicException::class));
        } catch (LogicException $exception) {
            //
        }
    }

    public function testARedirectCanBeFollowed()
    {
        Route::get('test', function () {
            return new RedirectResponse('test2');
        });

        Route::get('test2', function () {
            return 'Hello world2!';
        });

        $this->followingRedirects()
            ->get('test')
            ->assertStatus(200)
            ->assertSee('Hello world2!');

        try {
            $this->followingRedirects()->get('test');
            $this->fail(sprintf('A %s exception should have been thrown.', LogicException::class));
        } catch (LogicException $exception) {
            //
        }
    }
}
