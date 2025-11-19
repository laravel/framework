<?php

namespace Illuminate\Mail\Transport;

use Exception;
use Mailtrap\Api\EmailsSendApiInterface;
use Mailtrap\Mime\MailtrapEmail;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

class MailtrapTransport extends AbstractTransport
{
    /**
     * Create a new Mailtrap transport instance.
     */
    public function __construct(protected EmailsSendApiInterface $mailtrap)
    {
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $envelope = $message->getEnvelope();

        $headers = [];

        $headersToBypass = ['from', 'to', 'cc', 'bcc', 'reply-to', 'sender', 'subject', 'content-type'];

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

                if ($contentType == 'text/calendar') {
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

        try {
            // TODO Add headers.
            // TODO Add attachments.
            $email = (new MailtrapEmail)
                ->from($envelope->getSender())
                ->to(...$this->getRecipients($email, $envelope))
                ->cc(...$email->getCc())
                ->bcc(...$email->getBcc())
                ->replyTo(...$email->getReplyTo())
                ->subject($email->getSubject())
                ->html($email->getHtmlBody())
                ->text($email->getTextBody());

            $this->mailtrap->send($email);

            // TODO Update this exception to be correct
            throw_if(
                isset($result['statusCode']) && $result['statusCode'] != Response::HTTP_OK,
                Exception::class,
                $result['message'],
            );
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf('Request to Mailtrap API failed. Reason: %s.', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception
            );
        }
    }

    /**
     * Get the recipients without CC or BCC.
     */
    protected function getRecipients(Email $email, Envelope $envelope): array
    {
        return array_filter($envelope->getRecipients(), function (Address $address) use ($email): bool {
            return in_array($address, array_merge($email->getCc(), $email->getBcc()), true) === false;
        });
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'mailtrap';
    }
}
