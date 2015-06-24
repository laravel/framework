<?php

namespace Illuminate\Mail\Transport;

use Swift_Mime_Message;
use Swift_Events_SendEvent;
use Swift_Events_EventListener;

abstract class Transport
{
    /**
     * The plug-ins registered with the transport.
     *
     * @var array
     */
    public $plugins = [];

    /**
     * Register a plug-in with the transport.
     *
     * @param  Swift_Events_EventListener  $plugin
     * @return void
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
        array_push($this->plugins, $plugin);
    }

    /**
     * Iterate through registered plugins and execute plugins' methods.
     *
     * @param  Swift_Mime_Message $message
     * @return void
     */
    protected function beforeSendPerformed(Swift_Mime_Message $message)
    {
        foreach ($this->plugins as $plugin) {
            $evt = new Swift_Events_SendEvent($this, $message);

            $plugin->beforeSendPerformed($evt);
        }
    }
}
