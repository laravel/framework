<?php

namespace Illuminate\Mail\Events;

use Illuminate\Mail\SentMessage;

class MessageSent
{
    /**
     * The Symfony Email instance.
     *
     * @var \Symfony\Component\Mime\Email
     */
    public $message;

    /**
     * The Illuminate SentMessage instance.
     *
     * @var \Illuminate\Mail\SentMessage
     */
    public $sent;

    /**
     * The message data.
     *
     * @var array
     */
    public $data;

    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Mail\SentMessage  $message
     * @param  array  $data
     * @return void
     */
    public function __construct(SentMessage $message, array $data = [])
    {
        $this->data = $data;
        $this->sent = $message;
        $this->message = $message->getOriginalMessage();
    }

    /**
     * Get the serializable representation of the object.
     *
     * @return array
     */
    public function __serialize()
    {
        $hasAttachments = collect($this->message->getAttachments())->isNotEmpty();

        return $hasAttachments ? [
            'message' => base64_encode(serialize($this->message)),
            'sent' => base64_encode(serialize($this->sent)),
            'data' => base64_encode(serialize($this->data)),
            'hasAttachments' => true,
        ] : [
            'message' => $this->message,
            'sent' => $this->sent,
            'data' => $this->data,
            'hasAttachments' => false,
        ];
    }

    /**
     * Marshal the object from its serialized data.
     *
     * @param  array  $data
     * @return void
     */
    public function __unserialize(array $data)
    {
        if (isset($data['hasAttachments']) && $data['hasAttachments'] === true) {
            $this->message = unserialize(base64_decode($data['message']));
            $this->sent = unserialize(base64_decode($data['sent']));
            $this->data = unserialize(base64_decode($data['data']));
        } else {
            $this->message = $data['message'];
            $this->sent = $data['sent'];
            $this->data = $data['data'];
        }
    }
}
