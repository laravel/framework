<?php

use Aws\Ses\SesClient;
use Illuminate\Foundation\Application;
use Illuminate\Mail\TransportManager;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Support\Collection;

class MailSesTransportTest extends PHPUnit_Framework_TestCase {

	public function testGetTransport()
	{
		/** @var Application $app */
		$app = [
			'config' => new Collection([
				'services.ses' => [
					'key'    => 'foo',
					'secret' => 'bar',
					'region' => 'baz',
				]
			])
		];

		$manager = new TransportManager($app);

		/** @var SesTransport $transport */
		$transport = $manager->driver('ses');

		/** @var SesClient $ses */
		$ses = $this->readAttribute($transport, 'ses');

		$this->assertEquals('baz', $ses->getRegion());
	}

	public function testSend()
	{
		$message = new Swift_Message('Foo subject', 'Bar body');
		$message->setSender('myself@example.com');
		$message->setTo('me@example.com');
		$message->setBcc('you@example.com');

		$client = $this->getMockBuilder('Aws\Ses\SesClient')
			->setMethods(['sendRawEmail'])
			->disableOriginalConstructor()
			->getMock();
		$transport = new SesTransport($client);

		// Version 3 of the SDK base64 encodes the message automatically, but
		// since we mocking away the whole SDK client, we need to simulate it.
		$expectedData = defined('Aws\Sdk::VERSION')
			? strval($message)
			: base64_encode($message);

		$client->expects($this->once())
			->method('sendRawEmail')
			->with($this->equalTo([
				'Source' => 'myself@example.com',
				'Destinations' => [
					'me@example.com',
					'you@example.com',
				],
				'RawMessage' => ['Data' => $expectedData],
			]));

		$transport->send($message);
	}
}
