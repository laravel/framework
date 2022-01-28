<?php

namespace Illuminate\Mail\Transport;

use InvalidArgumentException;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesHttpAsyncAwsTransport;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

class SesTransport implements TransportInterface
{
    /**
     * The Amazon SES Symfony transport.
     *
     * @var \Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport|\Symfony\Component\Mailer\Bridge\Amazon\Transport\SesHttpAsyncAwsTransport
     */
    protected $transport;

    /**
     * The Amazon SES transmission options.
     *
     * @var array
     */
    protected $options;

    /**
     * Create a new SES transport instance.
     *
     * @param  \Symfony\Component\Mailer\Bridge\Amazon\Transport\SesApiAsyncAwsTransport|\Symfony\Component\Mailer\Bridge\Amazon\Transport\SesHttpAsyncAwsTransport  $transport
     * @param  array  $options
     * @return void
     */
    public function __construct(TransportInterface $transport, array $options = [])
    {
        if (! $transport instanceof SesApiAsyncAwsTransport || ! $transport instanceof SesHttpAsyncAwsTransport) {
            throw new InvalidArgumentException(
                'The SesTransport only accepts a SesHttpAsyncAwsTransport or a SesApiAsyncAwsTransport.'
            );
        }

        $this->transport = $transport;
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if ($message instanceof Email) {
            if (isset($this->options['ConfigurationSetName'])) {
                $message->getHeaders()->addTextHeader(
                    'X-SES-CONFIGURATION-SET', $this->options['ConfigurationSetName']
                );
            }

            if (isset($this->options['FromEmailAddressIdentityArn'])) {
                $message->getHeaders()->addTextHeader(
                    'X-SES-SOURCE-ARN', $this->options['FromEmailAddressIdentityArn']
                );
            }
        }

        return $this->transport->send($message, $envelope);
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->transport->__toString();
    }

    /**
     * Get the transmission options being used by the transport.
     *
     * @return array
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
