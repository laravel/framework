<?php

namespace Illuminate\Notifications\Messages;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Arr;

class DatabaseMessage
{
    /**
     * The message's unique identifier.
     *
     * @var string
     */
    public $id;

    /**
     * The data that should be stored with the notification.
     *
     * @var array
     */
    public $data = [];

    /**
     * Create a new database message.
     *
     * @param  array  $data
     * @return void
     */
    public function __construct(array $data = [])
    {
        $this->id = Arr::pull($data, 'id') ?: Uuid::uuid4();

        $this->data = $data;
    }
}
