<?php

namespace Illuminate\Tests\Console;

use Orchestra\Testbench\TestCase;

class HelloCommandTest extends TestCase
{
    /** @test */
    public function it_can_run_hello_command()
    {
        $this->artisan('hello', ['name' => 'baxtlyor'])
             ->expectsOutput('Hello, baxtlyor!')
             ->assertExitCode(0);
    }
}
