<?php

use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class ExceptionHandlerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->app->make('router')->get('exception', function () {
            throw new \Exception('I must fail');
        });
    }

    /**
     * @test
     */
    public function it_displays_html_full_error_if_debug_on()
    {
        $this->app['config']->set('app.debug', true);

        $response = $this->get('exception')->getContent();

        $this->assertContains('<!DOCTYPE html>', $response);
        $this->assertContains('Whoops, looks like something went wrong.', $response);
        $this->assertContains('I must fail', $response);
        $this->assertContains('::main()', $response);
        $this->assertNotContains('"message":', $response);
    }

    /**
     * @test
     */
    public function it_displays_html_error_without_details_if_debug_off()
    {
        $this->app['config']->set('app.debug', false);

        $response = $this->get('exception')->getContent();

        $this->assertContains('<!DOCTYPE html>', $response);
        $this->assertContains('Whoops, looks like something went wrong.', $response);
        $this->assertNotContains('I must fail', $response);
        $this->assertNotContains('::main()', $response);
    }

    /**
     * @test
     */
    public function it_displays_json_full_error_if_debug_on()
    {
        $this->app['config']->set('app.debug', true);

        $response = json_decode($this->get('exception', ['Accept' => 'application/json'])->getContent());

        $this->assertEquals($response->message, 'I must fail');
        $this->assertEquals($response->file, __FILE__);
        $this->assertObjectHasAttribute('line', $response);
        $this->assertObjectHasAttribute('trace', $response);
    }

    /**
     * @test
     */
    public function it_displays_json_error_without_details_if_debug_off()
    {
        $this->app['config']->set('app.debug', false);

        $response = json_decode($this->get('exception', ['Accept' => 'application/json'])->getContent());

        $this->assertEquals($response->message, 'Server Error');
        $this->assertObjectNotHasAttribute('file', $response);
        $this->assertObjectNotHasAttribute('line', $response);
        $this->assertObjectNotHasAttribute('trace', $response);
    }
}
