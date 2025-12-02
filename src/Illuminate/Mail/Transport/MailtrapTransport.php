<?php

namespace Illuminate\Mail\Transport;

use Exception;
use Mailtrap\Api\EmailsSendApiInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
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

        try {
            $this->mailtrap->send($email);
        } catch (Exception $exception) {
            throw new TransportException(
                sprintf('Request to Mailtrap API failed. Reason: %s.', $exception->getMessage()),
                is_int($exception->getCode()) ? $exception->getCode() : 0,
                $exception
            );
        }
    }

    /**
     * Get the string representation of the transport.
     */
    public function __toString(): string
    {
        return 'mailtrap';
    }
}
