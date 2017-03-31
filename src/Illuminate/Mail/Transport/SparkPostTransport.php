<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class SparkPostTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Transmission options.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  array  $options
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $options = [])
    {
        $this->key = $key;
        $this->client = $client;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $recipients = $this->getRecipients($message);

        $message->setBcc([]);

        $response = $this->client->post('https://api.sparkpost.com/api/v1/transmissions', [
            'headers' => [
                'Authorization' => $this->key,
            ],
            'json' => array_merge([
                'recipients' => $recipients,
                'content' => [
                    'email_rfc822' => $message->toString(),
                ],
            ], $this->options),
        ]);

        $message->getHeaders()->addTextHeader(
            'X-SparkPost-Transmission-ID', $this->getTransmissionId($response)
        );

        $this->sendPerformed($message);

        return $this->numberOfRecipients($message);
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * Note that SparkPost still respects CC, BCC headers in raw message itself.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getRecipients(Swift_Mime_Message $message)
    {
        $recipients = [];

        foreach ((array) $message->getTo() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }

        foreach ((array) $message->getCc() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }

        foreach ((array) $message->getBcc() as $email => $name) {
            $recipients[] = ['address' => compact('name', 'email')];
        }

        return $recipients;
    }

    /**
     * Get the transmission ID from the response.
     *
     * @param \GuzzleHttp\Psr7\Response $response
     * @return string
     */
    protected function getTransmissionId($response)
    {
        return object_get(
            json_decode($response->getBody()->getContents()), 'results.id'
        );
    }

    /**
     * Get the API key being used by the transport.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the API key being used by the transport.
     *
     * @param  string  $key
     * @return string
     */
    public function setKey($key)
    {
        return $this->key = $key;
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return string
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set the transmission options being used by the transport.
     *
     * @param  array  $options
     * @return array
     */
    public function setOptions(array $options)
    {
        return $this->options = $options;
    }
}
