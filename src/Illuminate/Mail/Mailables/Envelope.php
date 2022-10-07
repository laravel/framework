<?php

namespace Illuminate\Mail\Mailables;

class Envelope
{
    /**
     * The address sending the message.
     *
     * @var \Illuminate\Mail\Mailables\Address|string|null
     */
    public $from;

    /**
     * The recipients of the message.
     *
     * @var array
     */
    public $to;

    /**
     * The recipients receiving a copy of the message.
     *
     * @var array
     */
    public $cc;

    /**
     * The recipients receiving a blind copy of the message.
     *
     * @var array
     */
    public $bcc;

    /**
     * The recipients that should be replied to.
     *
     * @var array
     */
    public $replyTo;

    /**
     * The subject of the message.
     *
     * @var string|null
     */
    public $subject;

    /**
     * The message's tags.
     *
     * @var array
     */
    public $tags = [];

    /**
     * The message's meta data.
     *
     * @var array
     */
    public $metadata = [];

    /**
     * Create a new message envelope instance.
     *
     * @param  \Illuminate\Mail\Mailables\Address|string|null  $from
     * @param  array  $to
     * @param  array  $cc
     * @param  array  $bcc
     * @param  array  $replyTo
     * @param  string|null  $subject
     * @param  array  $tags
     * @param  array  $metadata
     * @return void
     */
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

    /**
     * Normalize the given array of addresses.
     *
     * @param  array  $addresses
     * @return array
     */
    protected function normalizeAddresses($addresses)
    {
        return collect($addresses)->map(function ($address) {
            return is_string($address) ? new Address($address) : $address;
        })->all();
    }

    /**
     * Add additional recipients to the message.
     *
     * @param  string|\Illuminate\Mail\Mailables\Address|array  $address
     * @return $this
     */
    public function addTo($address)
    {
        $this->addRecipient($address, 'to');

        return $this;
    }

    /**
     * Add "cc" recipients to the message.
     *
     * @param  string|\Illuminate\Mail\Mailables\Address|array  $address
     * @return $this
     */
    public function addCc($address)
    {
        $this->addRecipient($address, 'cc');

        return $this;
    }

    /**
     * Add "bcc" recipients to the message.
     *
     * @param  string|\Illuminate\Mail\Mailables\Address|array  $address
     * @return $this
     */
    public function addBcc($address)
    {
        $this->addRecipient($address, 'bcc');

        return $this;
    }

    /**
     * Add "reply to" recipients to the message.
     *
     * @param  string|\Illuminate\Mail\Mailables\Address|array  $address
     * @return $this
     */
    public function addReplyTo($address)
    {
        $this->addRecipient($address, 'replyTo');

        return $this;
    }

    /**
     * Add additional recipients to the message.
     *
     * @param  string|\Illuminate\Mail\Mailables\Address|array  $address
     * @param $type
     * @return $this
     *
     * @throws \InvalidArgumentException
     */
    protected function addRecipient($address, $type)
    {
        if (! in_array($type, ['to', 'cc', 'bcc', 'replyTo'])) {
            throw new \InvalidArgumentException("$type is not a valid recipient type.");
        }

        $this->{$type} = [
            ...$this->{$type},
            ...$this->normalizeAddresses(is_array($address) ? $address : [$address]),
        ];

        return $this;
    }

    /**
     * Determine if the message is from the given address.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function isFrom(string $address, string $name = null)
    {
        if (is_null($name)) {
            return $this->from->address === $address;
        }

        return $this->from->address === $address &&
               $this->from->name === $name;
    }

    /**
     * Determine if the message has the given address as a recipient.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasTo(string $address, string $name = null)
    {
        return $this->hasRecipient($this->to, $address, $name);
    }

    /**
     * Determine if the message has the given address as a "cc" recipient.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasCc(string $address, string $name = null)
    {
        return $this->hasRecipient($this->cc, $address, $name);
    }

    /**
     * Determine if the message has the given address as a "bcc" recipient.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasBcc(string $address, string $name = null)
    {
        return $this->hasRecipient($this->bcc, $address, $name);
    }

    /**
     * Determine if the message has the given address as a "reply to" recipient.
     *
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    public function hasReplyTo(string $address, string $name = null)
    {
        return $this->hasRecipient($this->replyTo, $address, $name);
    }

    /**
     * Determine if the message has the given recipient.
     *
     * @param  array  $recipients
     * @param  string  $address
     * @param  string|null  $name
     * @return bool
     */
    protected function hasRecipient(array $recipients, string $address, ?string $name = null)
    {
        return collect($recipients)->contains(function ($recipient) use ($address, $name) {
            if (is_null($name)) {
                return $recipient->address === $address;
            }

            return $recipient->address === $address &&
                   $recipient->name === $name;
        });
    }

    /**
     * Determine if the message has the given subject.
     *
     * @param  string  $subject
     * @return bool
     */
    public function hasSubject(string $subject)
    {
        return $this->subject === $subject;
    }

    /**
     * Determine if the message has the given metadata.
     *
     * @param  string  $key
     * @param  string  $value
     * @return bool
     */
    public function hasMetadata(string $key, string $value)
    {
        return isset($this->metadata[$key]) && $this->metadata[$key] === $value;
    }
}
