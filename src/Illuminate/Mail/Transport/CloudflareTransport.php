<?php

namespace Illuminate\Mail\Transport;

use Exception;
use SensitiveParameter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class CloudflareTransport extends AbstractTransport
{
    /**
     * The HTTP Client instance.
     */
    protected HttpClientInterface $client;

    /**
     * Create a new Cloudflare transport instance.
     */
    public function __construct(
        protected string $accountId,
        #[SensitiveParameter] protected string $key,
        ?HttpClientInterface $client = null,
    ) {
        parent::__construct();

        $this->client = $client ?? HttpClient::create();
    }

    /**
     * {@inheritDoc}
     *
     * @throws TransportException
     */
    protected function doSend(SentMessage $message): void
    {
        try {
            $response = $this->client->request('POST', sprintf(
                'https://api.cloudflare.com/client/v4/accounts/%s/email/sending/send',
                $this->accountId,
            ), [
                'auth_bearer' => $this->key,
                'headers' => ['Accept' => 'application/json'],
                'json' => $this->getPayload($message),
            ]);

            $result = $response->toArray(false);
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf('Request to Cloudflare API failed. Reason: %s.', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception,
            );
        }

        throw_if(
            $response->getStatusCode() !== Response::HTTP_OK,
            TransportException::class,
            $result['errors'][0]['message'] ?? 'Unknown error',
            $response->getStatusCode(),
        );
    }

    /**
     * Get the Cloudflare payload for the given message.
     */
    protected function getPayload(SentMessage $message): array
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $envelope = $message->getEnvelope();

        return array_filter([
            'from' => $this->formatAddress($envelope->getSender()),
            'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->stringifyAddresses($email->getCc()),
            'bcc' => $this->stringifyAddresses($email->getBcc()),
            'reply_to' => ($replyTo = $email->getReplyTo()) ? $this->formatAddress($replyTo[0]) : null,
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'headers' => $this->getCustomHeaders($email),
            'attachments' => $this->getAttachments($email),
        ], fn ($value) => $value !== null && $value !== [] && $value !== '');
    }

    /**
     * Get the recipients without CC or BCC.
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email) {
            return in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    /**
     * Get the custom headers for the email, excluding the standard ones.
     */
    protected function getCustomHeaders(Email $email): array
    {
        $headers = [];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type'];

        foreach ($email->getHeaders()->all() as $name => $header) {
            if (in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    /**
     * Get the attachments formatted for the Cloudflare API.
     */
    protected function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();

            $attachments[] = [
                'content' => str_replace("\r\n", '', $attachment->bodyToString()),
                'filename' => $headers->getHeaderParameter('Content-Disposition', 'filename'),
                'type' => $headers->get('Content-Type')->getBody(),
                'disposition' => $headers->getHeaderBody('Content-Disposition') ?: 'attachment',
            ];
        }

        return $attachments;
    }

    /**
     * Get the address formatted for the Cloudflare API.
     *
     * @return string|array
     */
    protected function formatAddress(Address $address)
    {
        if ($address->getName()) {
            return [
                'name' => $address->getName(),
                'address' => $address->getAddress(),
            ];
        }

        return $address->getAddress();
    }

    /**
     * Get multiple addresses formatted as strings for the Cloudflare API.
     */
    protected function stringifyAddresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $a->getAddress(), $addresses);
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'cloudflare';
    }
}
