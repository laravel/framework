<?php

namespace Illuminate\Mail\Transport;

use Exception;
use SensitiveParameter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Header\MetadataHeader;
use Symfony\Component\Mailer\Header\TagHeader;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class LettermintTransport extends AbstractTransport
{
    /**
     * The HTTP client instance.
     */
    protected HttpClientInterface $client;

    /**
     * The headers that should not be forwarded as custom headers.
     */
    protected const BYPASS_HEADERS = [
        'from',
        'to',
        'cc',
        'bcc',
        'reply-to',
        'sender',
        'subject',
        'content-type',
        'message-id',
        'date',
        'mime-version',
        'x-lettermint-message-id',
    ];

    /**
     * Create a new Lettermint transport instance.
     */
    public function __construct(
        #[SensitiveParameter] protected string $token,
        protected ?string $route = null,
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
            $response = $this->client->request('POST', 'https://api.lettermint.co/v1/send', [
                'headers' => [
                    'Accept' => 'application/json',
                    'x-lettermint-token' => $this->token,
                ],
                'json' => $this->getPayload($message),
            ]);

            $result = $response->toArray(false);
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf('Request to Lettermint API failed. Reason: %s.', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception,
            );
        }

        throw_if(
            $response->getStatusCode() !== Response::HTTP_ACCEPTED,
            TransportException::class,
            $this->getErrorMessage($result),
            $response->getStatusCode(),
        );

        if ($messageId = $result['message_id'] ?? null) {
            MessageConverter::toEmail($message->getOriginalMessage())
                ->getHeaders()
                ->addTextHeader('X-Lettermint-Message-ID', $messageId);
        }
    }

    /**
     * Get the Lettermint payload for the given message.
     */
    protected function getPayload(SentMessage $message): array
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $envelope = $message->getEnvelope();

        return array_filter([
            'from' => $envelope->getSender()->toString(),
            'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->stringifyAddresses($email->getCc()),
            'bcc' => $this->stringifyAddresses($email->getBcc()),
            'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'headers' => $this->getCustomHeaders($email),
            'attachments' => $this->getAttachments($email),
            'route' => $this->route,
            'tag' => $this->getTag($email),
            'metadata' => $this->getMetadata($email),
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

        foreach ($email->getHeaders()->all() as $name => $header) {
            if ($header instanceof TagHeader || $header instanceof MetadataHeader) {
                continue;
            }

            if (in_array($name, self::BYPASS_HEADERS, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        return $headers;
    }

    /**
     * Get the attachments formatted for the Lettermint API.
     */
    protected function getAttachments(Email $email): array
    {
        $attachments = [];

        foreach ($email->getAttachments() as $attachment) {
            $headers = $attachment->getPreparedHeaders();

            $item = [
                'content' => str_replace("\r\n", '', $attachment->bodyToString()),
                'filename' => $headers->getHeaderParameter('Content-Disposition', 'filename'),
                'content_type' => $headers->get('Content-Type')->getBody(),
            ];

            if ($attachment->hasContentId()) {
                $item['content_id'] = $attachment->getContentId();
            }

            $attachments[] = $item;
        }

        return $attachments;
    }

    /**
     * Get the email tag, if available.
     */
    protected function getTag(Email $email): ?string
    {
        $tag = null;

        foreach ($email->getHeaders()->all() as $header) {
            if ($header instanceof TagHeader) {
                $tag = $header->getValue();
            }
        }

        return $tag;
    }

    /**
     * Get the email metadata, if available.
     *
     * @return array<string, string>
     */
    protected function getMetadata(Email $email): array
    {
        $metadata = [];

        foreach ($email->getHeaders()->all() as $header) {
            if ($header instanceof MetadataHeader) {
                $metadata[$header->getKey()] = $header->getValue();
            }
        }

        return $metadata;
    }

    /**
     * Get multiple addresses formatted as strings for the Lettermint API.
     */
    protected function stringifyAddresses(array $addresses): array
    {
        return array_map(fn (Address $address) => $address->toString(), $addresses);
    }

    /**
     * Get the error message from the Lettermint response.
     */
    protected function getErrorMessage(array $result): string
    {
        return $result['message'] ?? $result['error'] ?? 'Unknown error';
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'lettermint';
    }
}
