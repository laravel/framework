<?php

namespace Illuminate\Tests\Http;

use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;

class HttpClientTest extends TestCase
{
    public function testStubbedResponsesAreReturnedAfterFaking()
    {
        $factory = new Factory;
        $factory->fake();

        $response = $factory->post('http://laravel.com/test-missing-page');

        $this->assertTrue($response->ok());
    }

    public function testUrlsCanBeStubbedByPath()
    {
        $factory = new Factory;

        $factory->fake([
            'foo.com/*' => ['page' => 'foo'],
            'bar.com/*' => ['page' => 'bar'],
            '*' => ['page' => 'fallback'],
        ]);

        $fooResponse = $factory->post('http://foo.com/test');
        $barResponse = $factory->post('http://bar.com/test');
        $fallbackResponse = $factory->post('http://fallback.com/test');

        $this->assertEquals('foo', $fooResponse['page']);
        $this->assertEquals('bar', $barResponse['page']);
        $this->assertEquals('fallback', $fallbackResponse['page']);
    }
}
