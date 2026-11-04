<?php

namespace Illuminate\Tests\Integration\Bus;

use Illuminate\Support\Facades\Log;
use Mockery;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class DispatcherTest extends TestCase
{
    /** {@inheritdoc} */
    protected function defineRoutes($router)
    {
        $router->get('/test', function () {
            dispatch(fn () => throw new RuntimeException('thrown after response'))
                ->afterResponse();

            return response('response before dispatch');
        });
    }

    public function test_dispatching_after_response_does_not_interrupt_response_output_even_with_exceptions()
    {
        Log::expects('error')
            ->with('thrown after response', Mockery::type('array'));

        $this->get('/test')
            ->assertSee('response before dispatch')
            ->assertStatus(200);
    }
}
