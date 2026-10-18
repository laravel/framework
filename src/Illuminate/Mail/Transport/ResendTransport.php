<?php

namespace Illuminate\Mail\Transport;

use Exception;
use Resend\Contracts\Client;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

/*
MIT License

Copyright (c) 2023 Jayan Ratna

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
class ResendTransport extends AbstractTransport implements BatchTransport
{
    /**
     * Create a new Resend transport instance.
     *
     * @param  \Resend\Contracts\Client  $resend
     * @param  string  $batchValidation  "strict" or "permissive"
     */
    public function __construct(protected Client $resend, protected string $batchValidation = 'permissive')
    {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportException
     * @throws \Throwable
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        try {
            $result = $this->resend->emails->send(
                $this->toPayload($email, $message->getEnvelope())
            );

            if (isset($result['statusCode']) && $result['statusCode'] != Response::HTTP_OK) {
                throw new Exception($result['message']);
            }
        } catch (Exception $exception) {
            throw $this->requestFailed($exception);
        }

        $this->markAsSent($message, $result->id);
    }

    /**
     * {@inheritDoc}
     */
    public function maxBatchSize(): int
    {
        return 100;
    }

    /**
     * {@inheritDoc}
     */
    public function canBatch(Email $email): bool
    {
        // Resend's batch endpoint does not support attachments yet...
        return $email->getAttachments() === [];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Symfony\Component\Mailer\Exception\TransportException
     */
    public function sendBatch(array $messages): array
    {
        $payloads = array_map(function (SentMessage $message) {
            $payload = $this->toPayload(
                MessageConverter::toEmail($message->getOriginalMessage()), $message->getEnvelope()
            );

            unset($payload['attachments']);

            return $payload;
        }, array_values($messages));

        try {
            $result = $this->resend->batch->send($payloads, [
                'batch_validation' => $this->batchValidation,
            ]);
        } catch (Exception $exception) {
            throw $this->requestFailed($exception);
        }

        return $this->mapBatchResponse($messages, $result);
    }

    /**
     * Map the Resend batch response onto the given messages, returning the failures.
     *
     * In permissive mode, Resend reports the emails that failed validation by their position
     * in the request, and returns the IDs of the emails it accepted in order. A response we
     * can't match up reliably fails every message, since we can't tell which were sent.
     *
     * @param  array<int, \Symfony\Component\Mailer\SentMessage>  $messages
     * @param  \Resend\Collection  $result
     * @return array<int, \Throwable>
     */
    protected function mapBatchResponse(array $messages, $result): array
    {
        $indexes = array_keys($messages);

        $failures = [];

        foreach ((array) ($result->getAttribute('errors') ?? []) as $error) {
            $position = $error['index'] ?? null;

            if (! is_int($position) || ! isset($indexes[$position]) || isset($failures[$indexes[$position]])) {
                return $this->unreadableBatchResponse($messages);
            }

            $failures[$indexes[$position]] = new TransportException(
                sprintf('Resend rejected the email. Reason: %s.', $error['message'] ?? 'Unknown error')
            );
        }

        $accepted = array_keys(array_diff_key($messages, $failures));

        $emails = array_values((array) ($result->getAttribute('data') ?? []));

        if (count($emails) !== count($accepted)) {
            $failures += $this->unreadableBatchResponse(array_diff_key($messages, $failures));

            ksort($failures);

            return $failures;
        }

        foreach ($accepted as $position => $index) {
            $messageId = is_array($emails[$position]) ? ($emails[$position]['id'] ?? null) : ($emails[$position]->id ?? null);

            if (! is_string($messageId) || $messageId === '') {
                $failures[$index] = new TransportException('Resend did not return an ID for the email.');

                continue;
            }

            $this->markAsSent($messages[$index], $messageId);
        }

        return $failures;
    }

    /**
     * Fail every given message because the Resend batch response could not be read.
     *
     * @param  array<int, \Symfony\Component\Mailer\SentMessage>  $messages
     * @return array<int, \Throwable>
     */
    protected function unreadableBatchResponse(array $messages): array
    {
        return array_map(fn () => new TransportException(
            'Unable to match the Resend API response to the email. It may or may not have been sent.'
        ), $messages);
    }

    /**
     * Build the Resend API payload for the given email.
     *
     * @param  \Symfony\Component\Mime\Email  $email
     * @param  \Symfony\Component\Mailer\Envelope  $envelope
     * @return array
     */
    protected function toPayload(Email $email, Envelope $envelope): array
    {
        $headers = [];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type', 'x-resend-email-id'];

        foreach ($email->getHeaders()->all() as $name => $header) {
            if (in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $attachments = [];

        if ($email->getAttachments()) {
            foreach ($email->getAttachments() as $attachment) {
                $attachmentHeaders = $attachment->getPreparedHeaders();
                $contentType = $attachmentHeaders->get('Content-Type')->getBody();
                $disposition = $attachmentHeaders->getHeaderBody('Content-Disposition');
                $filename = $attachmentHeaders->getHeaderParameter('Content-Disposition', 'filename');

                if ($contentType === 'text/calendar') {
                    $content = $attachment->getBody();
                } else {
                    $content = str_replace("\r\n", '', $attachment->bodyToString());
                }

                $item = [
                    'content_type' => $contentType,
                    'content' => $content,
                    'filename' => $filename,
                ];

                if ($disposition === 'inline') {
                    $item['content_id'] = $attachment->hasContentId() ? $attachment->getContentId() : $filename;
                }

                $attachments[] = $item;
            }
        }

        return [
            'from' => $envelope->getSender()->toString(),
            'to' => $this->stringifyAddresses($this->getRecipients($email, $envelope)),
            'cc' => $this->stringifyAddresses($email->getCc()),
            'bcc' => $this->stringifyAddresses($email->getBcc()),
            'reply_to' => $this->stringifyAddresses($email->getReplyTo()),
            'headers' => $headers,
            'subject' => $email->getSubject(),
            'html' => $email->getHtmlBody(),
            'text' => $email->getTextBody(),
            'attachments' => $attachments,
        ];
    }

    /**
     * Record the Resend email ID on the given sent message.
     *
     * @param  \Symfony\Component\Mailer\SentMessage  $message
     * @param  string  $messageId
     * @return void
     */
    protected function markAsSent(SentMessage $message, string $messageId): void
    {
        $message->setMessageId($messageId);

        if ($message->getOriginalMessage() instanceof \Symfony\Component\Mime\Message) {
            $headers = $message->getOriginalMessage()->getHeaders();

            $headers->remove('X-Resend-Email-ID');
            $headers->addHeader('X-Resend-Email-ID', $messageId);
        }
    }

    /**
     * Create an exception for a failed request to the Resend API.
     *
     * @param  \Throwable  $exception
     * @return \Symfony\Component\Mailer\Exception\TransportException
     */
    protected function requestFailed(Throwable $exception): TransportException
    {
        return new TransportException(
            sprintf('Request to Resend API failed. Reason: %s.', $exception->getMessage()),
            is_int($exception->getCode()) ? $exception->getCode() : 0,
            $exception
        );
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
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'resend';
    }
}
