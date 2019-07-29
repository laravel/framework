<?php

namespace Illuminate\Mail;

use Illuminate\Support\Arr;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\NamedAddress;
use Illuminate\Support\Traits\ForwardsCalls;

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
    public function __construct($email)
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
        $this->email->from(...$this->createAddress($address, $name));

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
        $this->email->sender($address);

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
            $this->email->to(...$this->createAddress($address, $name));

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
            $this->email->cc(...$this->createAddress($address, $name));

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
            $this->email->bcc(...$this->createAddress($address, $name));

            return $this;
        }

        return $this->addAddresses($address, $name, 'Bcc');
    }

    /**
     * Add a reply to address to the message.
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

            $this->email->$type(
                ...$this->createAddress($address, $name)
            );
        } else {
            $this->email->{"add{$type}"}(
                ...$this->createAddress($address, $name)
            );
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
        $this->email->attachFromPath(
            $file,
            Arr::get($options, 'as'),
            Arr::get($options, 'mime')
        );

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
        $this->email->attach($data, $name, Arr::get($options, 'mime'));

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
        if (isset($this->embeddedFiles[$file])) {
            return $this->embeddedFiles[$file];
        }

        return $this->embeddedFiles[$file] = $this->email->embedFromPath($file);
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
        return $this->email->embed($data, $name, $contentType);
    }

    /**
     * @param  string|array  $address
     * @param  string|null  $name
     * @return array
     */
    public function createAddress($address, $name = null)
    {
        $addrs = [];

        if (is_array($address)) {
            foreach ($address as $email) {
                $addrs[] = current($this->createAddress($email));
            }
        } else {
            if ($name) {
                $addrs[] = new NamedAddress($address, $name);
            } else {
                $addrs[] = new Address($address);
            }
        }

        return $addrs;
    }

    /**
     * Get the underlying Symfony Email instance.
     *
     * @return \Symfony\Component\Mime\Email
     */
    public function getSymfonyEmail()
    {
        return $this->email;
    }

    /**
     * Dynamically pass missing methods to the Symfony Email instance.
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
