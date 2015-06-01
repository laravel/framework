<?php

namespace Illuminate\Contracts\Support;

interface MessageBag
{
    /**
     * Get the keys present in the message bag.
     *
     * @return array
     */
    public function keys();

    /**
     * Add a message to the bag.
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message);

    /**
     * Merge a new array of messages into the bag.
     *
     * @param  \Illuminate\Contracts\Support\MessageProvider|array  $messages
     * @return $this
     */
    public function merge($messages);

    /**
     * Determine if messages exist for a given key.
     *
     * @param  string  $key
     * @return bool
     */
    public function has($key = null);

    /**
     * Get the first message from the bag for a given key.
     *
     * @param  string  $key
     * @param  string  $format
     * @return string
     */
    public function first($key = null, $format = null);

    /**
     * Get all of the messages from the bag for a given key.
     *
     * @param  string  $key
     * @param  string  $format
     * @return array
     */
    public function get($key, $format = null);

    /**
     * Get all of the messages for every key in the bag.
     *
     * @param  string  $format
     * @return array
     */
    public function all($format = null);

    /**
     * Get the default message format.
     *
     * @return string
     */
    public function getFormat();

    /**
     * Set the default message format.
     *
     * @param  string  $format
     * @return $this
     */
    public function setFormat($format = ':message');

    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Get the number of messages in the container.
     *
     * @return int
     */
    public function count();

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray();
}
