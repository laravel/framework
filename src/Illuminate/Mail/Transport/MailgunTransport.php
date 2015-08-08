<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\ClientInterface;

class MailgunTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The Mailgun API key.
     *
     * @var string
     */
    protected $key;

    /**
     * The Mailgun domain.
     *
     * @var string
     */
    protected $domain;

    /**
     * Create a new Mailgun transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @param  string  $domain
     * @return void
     */
    public function __construct(ClientInterface $client, $key, $domain)
    {
        $this->setKey($key);
        $this->client = $client;
        $this->setDomain($domain);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $options = ['auth' => ['api', $this->key]];

        if (version_compare(ClientInterface::VERSION, '6') === 1) {
            $options['multipart'] = [
                ['name' => 'to', 'contents' => $this->getTo($message)],
                ['name' => 'message', 'contents' => (string) $message, 'filename' => 'message.mime'],
            ];
        } else {
            $options['body'] = [
                'to' => $this->getTo($message),
                'message' => new PostFile('message', (string) $message),
            ];
        }

        return $this->client->post($this->getEndPoint(), $options);
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $formatted = [];

        $contacts = array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );

        foreach ($contacts as $address => $display) {
            $formatted[] = $display ? $display." <$address>" : $address;
        }

        return implode(',', $formatted);
    }

    /**
     * Get the the Mailgun API end-point.
     *
     * @return string
     */
    protected function getEndPoint()
    {
        return 'https://api.mailgun.net/v3/'.$this->getDomain().'/messages.mime';
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
     * @return void
     */
    public function setKey($key)
    {
        $this->key = $key;
    }

    /**
     * Get the domain being used by the transport.
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set the domain being used by the transport.
     *
     * @param  string  $domain
     * @return void
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }
}
