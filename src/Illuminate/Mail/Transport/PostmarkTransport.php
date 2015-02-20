<?php namespace Illuminate\Mail\Transport;

use Swift_Transport;
use GuzzleHttp\Client;
use Swift_Mime_Message;
use Swift_Events_EventListener;

class PostmarkTransport implements Swift_Transport {

	/**
	 * The Postmark Server Token key.
	 *
	 * @var string
	 */
	protected $serverToken;

	/**
	 * Create a new Postmark transport instance.
	 *
	 * @param  string  $serverToken
	 * @return void
	 */
	public function __construct($serverToken)
	{
		$this->serverToken = $serverToken;
	}

	/**
	 * {@inheritdoc}
	 */
	public function isStarted()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function start()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function stop()
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function send(Swift_Mime_Message $message, &$failedRecipients = null)
	{
		$client = $this->getHttpClient();

		return $client->post('https://api.postmarkapp.com/email', [
			'headers' => [
				'X-Postmark-Server-Token' => $this->serverToken,
			],
			'json' => $this->getMessagePayload($message),
		]);
	}

	/**
	 * Convert email dictionary with emails and names
	 * to array of emails with names.
	 *
	 * @param  array  $emails
	 * @return array
	 */
	private function convertEmailsArray(array $emails)
	{
		$convertedEmails = array();
		foreach ($emails as $email => $name)
		{
			$convertedEmails[] = $name
			? '"'.str_replace('"', '\\"', $name)."\" <{$email}>"
			: $email;
		}
		return $convertedEmails;
	}

	/**
	 * Gets MIME parts that match the message type.
	 * Excludes parts of type \Swift_Mime_Attachment as those
	 * are handled later.
	 *
	 * @param  Swift_Mime_Message  $message
	 * @param  string              $mimeType
	 * @return Swift_Mime_MimePart
	 */
	private function getMIMEPart(\Swift_Mime_Message $message, $mimeType)
	{
		foreach ($message->getChildren() as $part)
		{
			if (strpos($part->getContentType(), $mimeType) === 0 &&  ! ($part instanceof \Swift_Mime_Attachment))
			{
				return $part;
			}
		}
	}

	/**
	 * Convert a Swift Mime Message to a Postmark Payload.
	 *
	 * @param  Swift_Mime_Message  $message
	 * @return object
	 */
	private function getMessagePayload(Swift_Mime_Message $message)
	{
		$payload = [];

		$this->processRecipients($payload, $message);

		$this->processMessageParts($payload, $message);

		if ($message->getHeaders())
		{
			$this->processHeaders($payload, $message);
		}

		return $payload;
	}

	/**
	 * Applies the recipients of the message into the API Payload.
	 *
	 * @param  array               $payload
	 * @param  Swift_Mime_Message  $message
	 * @return object
	 */
	private function processRecipients(&$payload, $message)
	{
		$payload['From'] = join(',', $this->convertEmailsArray($message->getFrom()));
		$payload['To'] = join(',', $this->convertEmailsArray($message->getTo()));
		$payload['Subject'] = $message->getSubject();

		if ($cc = $message->getCc())
		{
			$payload['Cc'] = join(',', $this->convertEmailsArray($cc));
		}
		if ($reply_to = $message->getReplyTo())
		{
			$payload['ReplyTo'] = join(',', $this->convertEmailsArray($reply_to));
		}
		if ($bcc = $message->getBcc())
		{
			$payload['Bcc'] = join(',', $this->convertEmailsArray($bcc));
		}
	}

	/**
	 * Applies the message parts and attachments
	 * into the API Payload.
	 *
	 * @param  array               $payload
	 * @param  Swift_Mime_Message  $message
	 * @return object
	 */
	private function processMessageParts(&$payload, $message)
	{
		//Get the primary message.
		switch ($message->getContentType())
		{
			case 'text/html':
			case 'multipart/alternative':
				$payload['HtmlBody'] = $message->getBody();
				break;
			default:
				$payload['TextBody'] = $message->getBody();
				break;
		}

		// Provide an alternate view from the secondary parts.
		if ($plain = $this->getMIMEPart($message, 'text/plain'))
		{
			$payload['TextBody'] = $plain->getBody();
		}
		if ($html = $this->getMIMEPart($message, 'text/html'))
		{
			$payload['HtmlBody'] = $html->getBody();
		}
		if ($message->getChildren())
		{
			$payload['Attachments'] = array();
			foreach ($message->getChildren() as $attachment)
			{
				if (is_object($attachment) and $attachment instanceof \Swift_Mime_Attachment)
				{
					$payload['Attachments'][] = array(
						'Name'        => $attachment->getFilename(),
						'Content'     => base64_encode($attachment->getBody()),
						'ContentType' => $attachment->getContentType(),
					);
				}
			}
		}
	}

	/**
	 * Applies the headers into the API Payload.
	 *
	 * @param  array               $payload
	 * @param  Swift_Mime_Message  $message
	 * @return object
	 */
	private function processHeaders(&$payload, $message)
	{
		$headers = [];

		foreach ($message->getHeaders()->getAll() as $key => $value)
		{
			$fieldName = $value->getFieldName();

			$excludedHeaders = ['Subject', 'Content-Type', 'MIME-Version', 'Date'];

			if ( ! in_array($fieldName, $excludedHeaders))
			{

				if ($value instanceof \Swift_Mime_Headers_UnstructuredHeader ||
					$value instanceof \Swift_Mime_Headers_OpenDKIMHeader)
				{
					array_push($headers, [
						"Name"  => $fieldName,
						"Value" => $value->getValue(),
					]);
				}
				else if ($value instanceof \Swift_Mime_Headers_DateHeader ||
					$value instanceof \Swift_Mime_Headers_IdentificationHeader ||
					$value instanceof \Swift_Mime_Headers_ParameterizedHeader ||
					$value instanceof \Swift_Mime_Headers_PathHeader)
				{
					array_push($headers, [
						"Name"  => $fieldName,
						"Value" => $value->getFieldBody(),
					]);

					if ($value->getFieldName() == 'Message-ID')
					{
						array_push($headers, [
							"Name"  => 'X-PM-KeepID',
							"Value" => 'true',
						]);
					}
				}
			}
		}
		$payload['Headers'] = $headers;
	}

	/**
	 * {@inheritdoc}
	 */
	public function registerPlugin(Swift_Events_EventListener $plugin)
	{
		//
	}

	/**
	 * Get a new HTTP client instance.
	 *
	 * @return \GuzzleHttp\Client
	 */
	protected function getHttpClient()
	{
		return new Client;
	}

	/**
	 * Get the API key being used by the transport.
	 *
	 * @return string
	 */
	public function getServerToken()
	{
		return $this->token;
	}

	/**
	 * Set the API Server Token being used by the transport.
	 *
	 * @param  string  $serverToken
	 * @return void
	 */
	public function setServerToken($serverToken)
	{
		return $this->serverToken = $serverToken;
	}

}
