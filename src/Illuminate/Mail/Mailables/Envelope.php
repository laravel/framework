<?php

namespace Illuminate\Mail\Mailables;

class Envelope
{
    public $from;
    public $to;
    public $cc;
    public $bcc;
    public $replyTo;
    public $subject;
    public $tags = [];
    public $metadata = [];

    public function __construct(Address|string $from = null, $to = [], $cc = [], $bcc = [], $replyTo = [], string $subject = null, array $tags = [], array $metadata = [])
    {
        $this->from = is_string($from) ? new Address($from) : $from;
        $this->to = $this->normalizeAddresses($to);
        $this->cc = $this->normalizeAddresses($cc);
        $this->bcc = $this->normalizeAddresses($bcc);
        $this->replyTo = $this->normalizeAddresses($replyTo);
        $this->subject = $subject;
        $this->tags = $tags;
        $this->metadata = $metadata;
    }

    protected function normalizeAddresses($addresses)
    {
        return collect($addresses)->map(function ($address) {
            return is_string($address) ? new Address($address) : $address;
        })->all();
    }

    public function isFrom($address, $name = null)
    {
        if (is_null($name)) {
            return $this->from->address === $address;
        }

        return $this->from->address === $address &&
               $this->from->name === $name;
    }

    public function hasTo($address, $name = null)
    {
        return $this->hasRecipient($this->to, $address, $name);
    }

    public function hasCc($address, $name = null)
    {
        return $this->hasRecipient($this->cc, $address, $name);
    }

    public function hasBcc($address, $name = null)
    {
        return $this->hasRecipient($this->bcc, $address, $name);
    }

    public function hasReplyTo($address, $name = null)
    {
        return $this->hasRecipient($this->replyTo, $address, $name);
    }

    protected function hasRecipient($recipients, $address, $name)
    {
        return collect($recipients)->contains(function ($recipient) use ($address, $name) {
            if (is_null($name)) {
                return $recipient->address === $address;
            }

            return $recipient->address === $address &&
                   $recipient->name === $name;
        });
    }

    public function hasSubject($subject)
    {
        return $this->subject === $subject;
    }

    public function hasMetadata($key, $value)
    {
        return isset($this->metadata[$key]) && $this->metadata[$key] === $value;
    }
}
