<?php

namespace Illuminate\Mail\Transport;

use SensitiveParameter;
use Symfony\Component\Mailer\Bridge\Azure\Transport\AzureApiTransport;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AzureTransport extends AbstractTransport
{
    /**
     * The underlying Azure API transport instance.
     */
    protected AzureApiTransport $azure;

    /**
     * Create a new Azure transport instance.
     */
    public function __construct(
        #[SensitiveParameter] protected string $key,
        protected string $endpoint,
        protected bool $disableTracking = false,
        protected string $apiVersion = '2023-03-31',
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct();

        $host = $this->parseHost($this->endpoint);

        $this->azure = (new AzureApiTransport(
            $this->key,
            'default',
            $this->disableTracking,
            $this->apiVersion,
            $client,
        ))->setHost($host);
    }

    /**
     * @throws \Symfony\Component\Mailer\Exception\TransportException
     */
    protected function doSend(SentMessage $message): void
    {
        $sent = $this->azure->send(
            $message->getOriginalMessage(),
            $message->getEnvelope(),
        );

        if ($sent !== null) {
            $message->setMessageId($sent->getMessageId());
        }
    }

    /**
     * Extract the hostname from the endpoint URL, stripping the protocol and trailing slash.
     */
    protected function parseHost(string $endpoint): string
    {
        $endpoint = rtrim($endpoint, '/');

        return parse_url($endpoint, PHP_URL_HOST) ?? $endpoint;
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'acs';
    }
}
