<?php

namespace Illuminate\Mail\Events;

use Illuminate\Mail\SentMessage;

class MessageSent
{
    /**
     * The sent message instance.
     *
     * @var \Illuminate\Mail\SentMessage
     */
    public $message;

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
        $this->message = $message;
    }

    /**
     * Get the original email message.
     *
     * @return \Symfony\Component\Mime\Email
     */
    public function original()
    {
        return $this->message->getOriginalMessage();
    }

    /**
     * Get the serializable representation of the object.
     *
     * @return array
     */
    public function __serialize()
    {
        $hasAttachments = collect($this->original()->getAttachments())->isNotEmpty();

        return $hasAttachments ? [
            'message' => base64_encode(serialize($this->message)),
            'data' => base64_encode(serialize($this->data)),
            'hasAttachments' => true,
        ] : [
            'message' => $this->message,
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
            $this->data = unserialize(base64_decode($data['data']));
        } else {
            $this->message = $data['message'];
            $this->data = $data['data'];
        }
    }
}
