<?php

namespace Illuminate\Mail\Transport;

use Swift_Encoding;
use Swift_MimePart;
use Swift_Attachment;
use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\Collection;

class SparkPostTransport extends Transport
{
    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SparkPost API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new SparkPost transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     * @return void
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->client = $client;
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $recipients = $this->getRecipients($message);

        $message->setBcc([]);

        $options = [
            'headers' => [
                'Authorization' => $this->key,
            ],
            'json' => [
                'recipients' => $recipients,
                'content' => [
                    'html' => $message->getBody(),
                    'text' => $this->getPlainBody($message),
                    'from' => $this->getFrom($message),
                    'reply_to' => $this->getReplyTo($message),
                    'subject' => $message->getSubject(),
                ],
            ],
        ];

        if ($attachments = $this->getAttachments($message) and ! $attachments->isEmpty()) {
            $options['json']['content']['attachments'] = $attachments->values();
        }

        return $this->client->post('https://api.sparkpost.com/api/v1/transmissions', $options);
    }

    /**
     * Get all the addresses this message should be sent to.
     *
     * Note that SparkPost still respects CC, BCC headers in raw message itself.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getRecipients(Swift_Mime_Message $message)
    {
        $fields = [
            'to' => $message->getTo() ? array_keys($message->getTo()) : [],
            'cc' => $message->getCc() ? array_keys($message->getCc()) : [],
            'bcc' => $message->getBcc() ? array_keys($message->getBcc()) : [],
        ];

        $all = array_collapse($fields);
        $visible = array_merge($fields['to'], $fields['cc']);

        $recipients = array_map(function ($address) use ($visible) {
            $header = count($visible) ? implode(',', $visible) : $address;

            return ['address' => ['email' => $address, 'header_to' => $header]];
        }, $all);

        return $recipients;
    }

    /**
     * Get the "from" contacts in the format required by SparkPost.
     *
     * @param  \Swift_Mime_Message  $message
     * @return array
     */
    protected function getFrom(Swift_Mime_Message $message)
    {
        return array_map(function ($email, $name) {
            return compact('name', 'email');
        }, array_keys($message->getFrom()), $message->getFrom())[0];
    }

    /**
     * Get the 'reply_to' headers and format as required by SparkPost.
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getReplyTo(Swift_Mime_Message $message)
    {
        if (is_array($message->getReplyTo())) {
            return current($message->getReplyTo()).' <'.key($message->getReplyTo()).'>';
        }
    }

    /**
     * Get plain body content of this entity as a string.
     *
     * @param  \Swift_Mime_Message  $message
     * @return string
     */
    protected function getPlainBody(Swift_Mime_Message $message)
    {
        $parts = Collection::make($message->getChildren())
                    ->filter(function ($item, $key) {
                        return $item instanceof Swift_MimePart;
                    })
                    ->first();

        return $parts ? $parts->getBody() : $message->getBody();
    }

    /**
     * Get message attachments.
     *
     * @param  \Swift_Mime_Message $message
     * @return \Illuminate\Support\Collection
     */
    protected function getAttachments(Swift_Mime_Message $message)
    {
        return Collection::make($message->getChildren())
                ->filter(function ($value, $key) {
                    return $value instanceof Swift_Attachment;
                })
                ->map(function ($item, $key) {
                    return [
                        'type' => $item->getContentType(),
                        'name' => $item->getFileName(),
                        'data' => Swift_Encoding::getBase64Encoding()->encodeString($item->getBody()),
                    ];
                });
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
}
