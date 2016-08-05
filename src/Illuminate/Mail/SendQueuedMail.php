<?php

namespace Illuminate\Mail;

use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use SuperClosure\Serializer;

class SendQueuedMail
{
    use SerializesModels;

    /**
     * View for main.
     *
     * @var string
     */
    protected $view;
    protected $data;
    protected $callback;

    /**
     * Create a new job instance.
     *
     * @param  string  $view
     * @param  array  $data
     * @param  string  $callback
     * @return void
     */
    public function __construct($view, $data, $callback)
    {
        $this->view = $view;
        $this->data = $data;
        $this->callback = $this->buildQueueCallable($callback);
    }

    /**
     * Handle the queued job.
     *
     * @param  MailerContract  $mailer
     * @return void
     */
    public function handle(MailerContract $mailer)
    {
        $mailer->send($this->view, $this->data, $this->getQueuedCallable($this->callback));
    }

    /**
     * Build the callable for a queued e-mail job.
     *
     * @param  \Closure|string  $callback
     * @return string
     */
    protected function buildQueueCallable($callback)
    {
        if (! $callback instanceof \Closure) {
            return $callback;
        }

        return (new Serializer)->serialize($callback);
    }

    /**
     * Get the true callable for a queued e-mail message.
     *
     * @param  string $callback
     * @return \Closure|string
     */
    protected function getQueuedCallable($callback)
    {
        if (Str::contains($callback, 'SerializableClosure')) {
            return (new Serializer)->unserialize($callback);
        }

        return $callback;
    }
}
