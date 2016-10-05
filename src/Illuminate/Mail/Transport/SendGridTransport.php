<?php

namespace Illuminate\Mail\Transport;

use Swift_Attachment;
use Swift_Image;
use Swift_Mime_Message;
use GuzzleHttp\ClientInterface;

class SendGridTransport extends Transport
{
	/**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\ClientInterface
     */
    protected $client;

    /**
     * The SendGrid API key.
     *
     * @var string
     */
    protected $key;

    /**
     * Create a new SendGrid transport instance.
     *
     * @param  \GuzzleHttp\ClientInterface  $client
     * @param  string  $key
     */
    public function __construct(ClientInterface $client, $key)
    {
        $this->client = $client;
        $this->key    = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
        $this->beforeSendPerformed($message);

        $this->client->post($this->getUrl(), $this->getOptions($message));

        return $this->numberOfRecipients($message);
    }

    /**
     * Get the options array.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getOptions(Swift_Mime_Message $message)
    {
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/json',
            ],
            'json' => [
                'personalizations' => [
                    [
                        'to'  => $this->getTo($message),
                        'cc'  => $this->getCc($message),
                        'bcc' => $this->getBcc($message),
                        'subject' => $message->getSubject(),
                    ],
                ],
                'from'        => $this->getFrom($message),
                'reply_to'    => $this->getReplyTo($message),
                'content'     => $this->getContent($message),
                'attachments' => $this->getAttachments($message),
            ],
        ];

        $options = array_filter_recursive($options);

        return $options;
    }

    /**
     * Get the "to" payload field for the API request.
     *
     * @param  \Swift_Mime_Message $message
     * @return array
     */
    protected function getTo(Swift_Mime_Message $message)
    {
        $to = [];

        foreach ((array) $message->getTo() as $email => $name) {
            $to[] = ['email' => $email, 'name' => $name];
        }

        return $to;
    }

    /**
     * Get the "cc" payload field for the API request.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getCc(Swift_Mime_Message $message)
    {
        $cc = [];

        foreach ((array) $message->getCc() as $email => $name) {
            $cc[] = ['email' => $email, 'name' => $name];
        }

        return $cc;
    }

    /**
     * Get the "bcc" payload field for the API request.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getBcc(Swift_Mime_Message $message)
    {
        $bcc = [];

        foreach ((array) $message->getBcc() as $email => $name) {
            $bcc[] = ['email' => $email, 'name' => $name];
        }

        return $bcc;
    }

    /**
     * Get the "from" payload field for the API request.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getFrom(Swift_Mime_Message $message)
    {
        $from = $message->getFrom();

        if ($from) {
            return ['email' => key($from)];
        }

        return [];
    }

    /**
     * Get the "reply_to" payload field for the API request.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getReplyTo(Swift_Mime_Message $message)
    {
        $replyTo = $message->getReplyTo();

        if ($replyTo) {
            return ['email' => key($replyTo)];
        }

        return [];
    }

    /**
     * Get the email content.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getContent(Swift_Mime_Message $message)
    {
        $content = [
            [
                'type'  => 'text/html',
                'value' => $message->getBody(),
            ],
        ];

        return $content;
    }

    /**
     * Get the email attachments.
     *
     * @param  Swift_Mime_Message  $message
     * @return array
     */
    protected function getAttachments(Swift_Mime_Message $message)
    {
        $attachments = [];

        foreach ($message->getChildren() as $attachment) {
            if ($attachment instanceof Swift_Attachment || $attachment instanceof Swift_Image) {
                $attachments[] = [
                    'content'     => base64_encode($attachment->getBody()),
                    'type'        => $attachment->getContentType(),
                    'filename'    => $attachment->getFilename(),
                    'disposition' => $attachment->getDisposition(),
                    'content_id'  => $attachment->getId(),
                ];
            }
        }

        return $attachments;
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    protected function getUrl()
    {
        return 'https://api.sendgrid.com/v3/mail/send';
    }
}