<?php

namespace Illuminate\Mail;

use Illuminate\Support\Str;
use Illuminate\Support\Traits\ForwardsCalls;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * @mixin \Symfony\Component\Mime\Email
 */
class Message
{
    use ForwardsCalls;

    /**
     * The Symfony Email instance.
     *
     * @var \Symfony\Component\Mime\Email
     */
    protected $message;

    /**
     * CIDs of files embedded in the message.
     *
     * @var array
     */
    protected $embeddedFiles = [];

    /**
     * Create a new message instance.
     *
     * @param  \Symfony\Component\Mime\Email  $message
     * @return void
     */
    public function __construct(Email $message)
    {
        $this->message = $message;
    }

    /**
     * Add a "from" address to the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        is_array($address)
            ? $this->message->from(...$address)
            : $this->message->from(new Address($address, (string) $name));

        return $this;
    }

    /**
     * Set the "sender" of the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function sender($address, $name = null)
    {
        is_array($address)
            ? $this->message->sender(...$address)
            : $this->message->sender(new Address($address, (string) $name));

        return $this;
    }

    /**
     * Set the "return path" of the message.
     *
     * @param  string  $address
     * @return $this
     */
    public function returnPath($address)
    {
        $this->message->returnPath($address);

        return $this;
    }

    /**
     * Add a recipient to the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function to($address, $name = null, $override = false)
    {
        if ($override) {
            is_array($address)
                ? $this->message->to(...$address)
                : $this->message->to(new Address($address, (string) $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'To');
    }

    /**
     * Remove all "to" addresses from the message.
     *
     * @return $this
     */
    public function forgetTo()
    {
        if ($header = $this->message->getHeaders()->get('To')) {
            $this->addAddressDebugHeader('X-To', $this->message->getTo());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a carbon copy to the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function cc($address, $name = null, $override = false)
    {
        if ($override) {
            is_array($address)
                ? $this->message->cc(...$address)
                : $this->message->cc(new Address($address, (string) $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Cc');
    }

    /**
     * Remove all carbon copy addresses from the message.
     *
     * @return $this
     */
    public function forgetCc()
    {
        if ($header = $this->message->getHeaders()->get('Cc')) {
            $this->addAddressDebugHeader('X-Cc', $this->message->getCC());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a blind carbon copy to the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @param  bool  $override
     * @return $this
     */
    public function bcc($address, $name = null, $override = false)
    {
        if ($override) {
            is_array($address)
                ? $this->message->bcc(...$address)
                : $this->message->bcc(new Address($address, (string) $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Remove all of the blind carbon copy addresses from the message.
     *
     * @return $this
     */
    public function forgetBcc()
    {
        if ($header = $this->message->getHeaders()->get('Bcc')) {
            $this->addAddressDebugHeader('X-Bcc', $this->message->getBcc());

            $header->setAddresses([]);
        }

        return $this;
    }

    /**
     * Add a "reply to" address to the message.
     *
     * @param  string|array  $address
     * @param  string|null  $name
     * @return $this
     */
    public function replyTo($address, $name = null)
    {
        return $this->addAddresses($address, $name, 'ReplyTo');
    }

    /**
     * Add a recipient to the message.
     *
     * @param  string|array  $address
     * @param  string  $name
     * @param  string  $type
     * @return $this
     */
    protected function addAddresses($address, $name, $type)
    {
        if (is_array($address)) {
            $type = lcfirst($type);

            $addresses = collect($address)->map(function (string|array $address, $key) {
                if (is_string($key) && is_string($address)) {
                    return new Address($key, $address);
                }

                if (is_array($address)) {
                    return new Address($address['email'] ?? $address['address'], $address['name'] ?? null);
                }

                return $address;
            })->all();

            $this->message->{"{$type}"}(...$addresses);
        } else {
            $this->message->{"add{$type}"}(new Address($address, (string) $name));
        }

        return $this;
    }

    /**
     * Add an address debug header for a list of recipients.
     *
     * @param  string  $header
     * @param  \Symfony\Component\Mime\Address[]  $addresses
     * @return $this
     */
    protected function addAddressDebugHeader(string $header, array $addresses)
    {
        $this->message->getHeaders()->addTextHeader(
            $header,
            implode(', ', array_map(fn ($a) => $a->toString(), $addresses)),
        );

        return $this;
    }

    /**
     * Set the subject of the message.
     *
     * @param  string  $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->message->subject($subject);

        return $this;
    }

    /**
     * Set the message priority level.
     *
     * @param  int  $level
     * @return $this
     */
    public function priority($level)
    {
        $this->message->priority($level);

        return $this;
    }

    /**
     * Attach a file to the message.
     *
     * @param  string  $file
     * @param  array  $options
     * @return $this
     */
    public function attach($file, array $options = [])
    {
        $this->message->attachFromPath($file, $options['as'] ?? null, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Attach in-memory data as an attachment.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  array  $options
     * @return $this
     */
    public function attachData($data, $name, array $options = [])
    {
        $this->message->attach($data, $name, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Embed a file in the message and get the CID.
     *
     * @param  string  $file
     * @return string
     */
    public function embed($file)
    {
        $cid = Str::random(10);

        $this->message->embedFromPath($file, $cid);

        return "cid:$cid";
    }

    /**
     * Embed in-memory data in the message and get the CID.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string|null  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        $this->message->embed($data, $name, $contentType);

        return "cid:$name";
    }

    /**
     * Get the underlying Symfony Email instance.
     *
     * @return \Symfony\Component\Mime\Email
     */
    public function getSymfonyMessage()
    {
        return $this->message;
    }

    /**
     * Dynamically pass missing methods to the Symfony instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->forwardDecoratedCallTo($this->message, $method, $parameters);
    }
}
