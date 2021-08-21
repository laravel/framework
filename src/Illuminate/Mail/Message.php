<?php

namespace Illuminate\Mail;

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
    protected $email;

    /**
     * CIDs of files embedded in the message.
     *
     * @var array
     */
    protected $embeddedFiles = [];

    /**
     * Create a new message instance.
     *
     * @param  \Symfony\Component\Mime\Email  $email
     * @return void
     */
    public function __construct(Email $email)
    {
        $this->email = $email;
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
            ? $this->email->from(...$address)
            : $this->email->from(new Address($address, (string) $name));

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
            ? $this->email->sender(...$address)
            : $this->email->sender(new Address($address, (string) $name));

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
        $this->email->returnPath($address);

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
            if (is_array($address)) {
                $this->email->to(...$address);
            } else {
                $this->email->to(new Address($address, (string) $name));
            }

            return $this;
        }

        return $this->addAddresses($address, $name, 'To');
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
                ? $this->email->cc(...$address)
                : $this->email->cc(new Address($address, (string) $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Cc');
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
            if (is_array($address)) {
                $this->email->bcc(...$address);
            } else {
                $this->email->bcc(new Address($address, (string) $name));
            }

            return $this;
        }

        return $this->addAddresses($address, $name, 'Bcc');
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

            $addresses = collect($address)->map(function (string | array $address) {
                if (is_array($address)) {
                    return new Address($address['email'] ?? $address['address'], $address['name'] ?? null);
                }

                return $address;
            })->all();

            $this->email->{"{$type}"}(...$addresses);
        } else {
            $this->email->{"add{$type}"}(new Address($address, (string) $name));
        }

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
        $this->email->subject($subject);

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
        $this->email->priority($level);

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
        $this->email->attachFromPath($file, $options['as'] ?? null, $options['mime'] ?? null);

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
        $this->email->attach($data, $name, $options['mime'] ?? null);

        return $this;
    }

    /**
     * Embed a file in the message.
     *
     * @param  string  $file
     * @return $this
     */
    public function embed($file)
    {
        $this->email->embedFromPath($file);

        return $this;
    }

    /**
     * Embed in-memory data in the message.
     *
     * @param  string  $data
     * @param  string  $name
     * @param  string|null  $contentType
     * @return string
     */
    public function embedData($data, $name, $contentType = null)
    {
        $this->email->embed($data, $name, $contentType);

        return $this;
    }

    /**
     * Get the underlying Symfony Email instance.
     *
     * @return \Symfony\Component\Mime\Email
     */
    public function getSymfonyMessage()
    {
        return $this->email;
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
        return $this->forwardCallTo($this->email, $method, $parameters);
    }
}
