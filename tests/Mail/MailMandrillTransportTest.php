<?php

use Illuminate\Mail\Transport\MandrillTransport;

class MailMandrillTransportTest extends PHPUnit_Framework_TestCase {

	public function testSend()
	{
		$message = new Swift_Message('Foo subject', 'Bar body');
		$message->setTo('me@example.com');
		$message->setBcc('you@example.com');

		$client = $this->getMock('GuzzleHttp\ClientInterface', array('post'));
		$transport = new MandrillTransport($client, 'testkey');

		$client->expects($this->once())
			->method('post')
			->with($this->equalTo('https://mandrillapp.com/api/1.0/messages/send-raw.json'),
				$this->equalTo([
					'form_params' => [
						'key'         => 'testkey',
						'raw_message' => $message->toString(),
						'async'       => false,
						'to'          => ['me@example.com', 'you@example.com'],
					],
				])
			);

		$transport->send($message);
	}
}
