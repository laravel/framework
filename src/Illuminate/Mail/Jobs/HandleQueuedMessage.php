<?php

namespace Illuminate\Mail\Jobs;

use Closure;
use Illuminate\Support\Str;
use SuperClosure\Serializer;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;

class HandleQueuedMessage
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * The name of the email view.
     *
     * @var string
     */
    public $view;

    /**
     * The data to be passed to the view.
     *
     * @param  array  $data
     */
    public $data;

    /**
     * The message configuration callback.
     *
     * @var \Closure
     */
    public $callback;

    /**
     * Create a new job instance.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  \Closure  $callback
     * @return void
     */
    public function __construct($view, $data, $callback)
    {
        $this->view = $view;
        $this->data = $data;
        $this->callback = $callback;
    }

    /**
     * Handle the queued job.
     *
     * @param  \Illuminate\Contracts\Mail\Mailer  $mailer
     * @return void
     */
    public function handle(Mailer $mailer)
    {
        $mailer->send($this->view, $this->data, $this->callback);
    }

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = $this->getSerializedPropertyValue($value);
        }

        if ($this->callback instanceof Closure) {
            $this->callback = (new Serializer)->serialize($this->callback);
        }

        return array_keys(get_object_vars($this));
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function __wakeup()
    {
        foreach ($this->data as $key => $value) {
            $this->data[$key] = $this->getRestoredPropertyValue($value);
        }

        if (Str::contains($this->callback, 'SerializableClosure')) {
            $this->callback = (new Serializer)->unserialize($this->callback);
        }
    }
}
