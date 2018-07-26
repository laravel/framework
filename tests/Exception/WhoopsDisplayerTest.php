<?php

use Illuminate\Exception\WhoopsDisplayer;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Mockery as m;

class WhoopsDisplayerTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testStatusAndHeadersAreSetInResponse()
	{
	    $run = new \Whoops\Run();
		$displayer = new WhoopsDisplayer($run, false);
		$headers = array('X-My-Test-Header' => 'HeaderValue');
		$exception = new HttpException(401, 'Unauthorized', null, $headers);
		$response = $displayer->display($exception);

		$this->assertTrue($response->headers->has('X-My-Test-Header'), "response headers should include headers provided to the exception");
		$this->assertEquals('HeaderValue', $response->headers->get('X-My-Test-Header'), "response header values should match those provided to the exception");
		$this->assertEquals(401, $response->getStatusCode(), "response status should match the status provided to the exception");
	}

}
