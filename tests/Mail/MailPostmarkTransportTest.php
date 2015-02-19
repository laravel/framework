<?php

class MailPostmarkTransportTest extends PHPUnit_Framework_TestCase {

	public function testSend()
	{
		$message = new Swift_Message('Is alive!', 'Doo-wah-ditty.');
		$message->setFrom('johnny5@example.com', 'Johnny #5');
		$message->addTo('you@example.com', 'A. Friend');
		$message->addTo('you+two@example.com');
		$message->addCc('another+1@example.com');
		$message->addCc('another+2@example.com', 'Extra 2');
		$message->addBcc('another+3@example.com');
		$message->addBcc('another+4@example.com', 'Extra 4');
		$message->addPart('<q>Help me Rhonda</q>', 'text/html');
		$message->attach(Swift_Attachment::newInstance('This is the plain text attachment.', 'hello.txt', 'text/plain'));
		$message->setPriority(1);

		$headers   = $message->getHeaders();
		$messageId = $headers->get('Message-ID')->getId();

		$transport = new PostmarkTransportStub('TESTING_SERVER');

		$client = $this->getMock('GuzzleHttp\Client', array('post'));
		$transport->setHttpClient($client);

		$client->expects($this->once())
		       ->method('post')
		       ->with($this->equalTo('https://api.postmarkapp.com/email'),
			       $this->equalTo([
				        'headers' => [
					        'X-Postmark-Server-Token' => 'TESTING_SERVER',
				        ],
				        'json'      => [
					        'From'     => '"Johnny #5" <johnny5@example.com>',
					        'To'       => '"A. Friend" <you@example.com>,you+two@example.com',
					        'Cc'       => 'another+1@example.com,"Extra 2" <another+2@example.com>',
					        'Bcc'      => 'another+3@example.com,"Extra 4" <another+4@example.com>',
					        'Subject'  => 'Is alive!',
					        'TextBody' => 'Doo-wah-ditty.',
					        'HtmlBody' => '<q>Help me Rhonda</q>',
					        'Headers'  => [
						        ['Name'   => 'Message-ID', 'Value'   => '<'.$messageId.'>'],
						        ['Name'   => 'X-PM-KeepID', 'Value'   => 'true'],
						        ['Name'   => 'X-Priority', 'Value'   => '1 (Highest)'],
					        ],
					        'Attachments' => [
						        [
							        'ContentType' => 'text/plain',
							        'Content'     => 'VGhpcyBpcyB0aGUgcGxhaW4gdGV4dCBhdHRhY2htZW50Lg==',
							        'Name'        => 'hello.txt',
						        ],
					        ],
				        ],
			        ])
		       );

		$transport->send($message);
	}
}

class PostmarkTransportStub extends \Illuminate\Mail\Transport\PostmarkTransport {
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
