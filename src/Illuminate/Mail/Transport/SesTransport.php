<?php

namespace Illuminate\Mail\Transport;

use Aws\Ses\SesClient;
use Swift_Mime_Message;

class SesTransport extends Transport
{
    /**
     * The Amazon SES instance.
     *
     * @var \Aws\Ses\SesClient
     */
    protected $ses;

    /**
     * Create a new SES transport instance.
     *
     * @param  \Aws\Ses\SesClient  $ses
     * @return void
     */
    public function __construct(SesClient $ses)
    {
        $this->ses = $ses;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        return $this->ses->sendRawEmail([
            'Source' => key($message->getSender() ?: $message->getFrom()),
            'Destinations' => $this->getTo($message),
            'RawMessage' => [
                'Data' => (string) $message,
            ],
        ]);
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $destinations = [];

        $contacts = array_merge(
            (array) $message->getTo(), (array) $message->getCc(), (array) $message->getBcc()
        );

        foreach ($contacts as $address => $display) {
            $destinations[] = $address;
        }

        return $destinations;
    }
}
