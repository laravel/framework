<?php

class MailMandrillTransportTest extends PHPUnit_Framework_TestCase {

	public function testSend()
	{
		$message = new Swift_Message('Foo subject', 'Bar body');
		$message->setTo('me@example.com');
		$message->setBcc('you@example.com');

		$transport = new MandrillTransportStub('testkey');
		$client    = $this->getMock('GuzzleHttp\Client', array('post'));
		$transport->setHttpClient($client);

		$client->expects($this->once())
			->method('post')
			->with($this->equalTo('https://mandrillapp.com/api/1.0/messages/send-raw.json'),
				$this->equalTo([
					'body' => [
						'key'         => 'testkey',
						'raw_message' => $message->toString(),
						'async'       => false,
						'to'          => ['me@example.com', 'you@example.com']
					]
				])
			);

		$transport->send($message);
	}
}

class MandrillTransportStub extends \Illuminate\Mail\Transport\MandrillTransport
{
	protected $client;

	protected function getHttpClient()
	{
		return $this->client;
	}

	public function setHttpClient($client)
	{
		$this->client = $client;
	}
}
