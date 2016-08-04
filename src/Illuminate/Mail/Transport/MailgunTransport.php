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
     * THe Mailgun API end-point.
     *
     * @var string
     */
    protected $url;

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
        $this->client = $client;
        $this->key = $key;
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
                ['name' => 'cc', 'contents' => $this->getCc($message)],
                ['name' => 'bcc', 'contents' => $this->getBcc($message)],
                ['name' => 'message', 'contents' => $message->toString(), 'filename' => 'message.mime'],
            ];
        } else {
            $options['body'] = [
                'to'      => $this->getTo($message),
                'cc'      => $this->getCc($message),
                'bcc'     => $this->getBcc($message),
                'message' => new PostFile('message', $message->toString()),
            ];
        }

        return $this->client->post($this->url, $options);
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        return $this->formatAddress($message->getTo());
    }

    /**
     * Get the "cc" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getCc(Swift_Mime_Message $message)
    {
        return $this->formatAddress($message->getCc());
    }

    /**
     * Get the "bcc" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getBcc(Swift_Mime_Message $message)
    {
        return $this->formatAddress($message->getBcc());
    }

    /**
     * Get Comma-Separated Address (with name, if available) for the API request.
     *
     * @param  array $contacts
     * @return string
     */
    protected function formatAddress($contacts)
    {
        $formatted = [];

        foreach ($contacts as $address => $display) {
            $formatted[] = $display ? $display." <$address>" : $address;
        }

        return implode(',', $formatted);
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
        $this->url = 'https://api.mailgun.net/v3/'.$domain.'/messages.mime';

        return $this->domain = $domain;
    }
}
