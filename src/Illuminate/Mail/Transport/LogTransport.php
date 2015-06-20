<?php

namespace Illuminate\Mail\Transport;

use Swift_Transport;
use Swift_Mime_Message;
use Swift_Mime_MimeEntity;
use Psr\Log\LoggerInterface;
use Swift_Events_EventListener;

class LogTransport implements Swift_Transport
{
    /**
     * The Logger instance.
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

	/**
	 * Plugins for transport
	 *
	 * @var array
	 */
	public $plugins = [];

    /**
     * Create a new log transport instance.
     *
     * @param  \Psr\Log\LoggerInterface  $logger
     * @return void
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Swift_Mime_Message $message, &$failedRecipients = null)
    {
		$this->beforeSendPerformed($message);

        $this->logger->debug($this->getMimeEntityString($message));
    }

    /**
     * Get a loggable string out of a Swiftmailer entity.
     *
     * @param  \Swift_Mime_MimeEntity $entity
     * @return string
     */
    protected function getMimeEntityString(Swift_Mime_MimeEntity $entity)
    {
        $string = (string) $entity->getHeaders().PHP_EOL.$entity->getBody();

        foreach ($entity->getChildren() as $children) {
            $string .= PHP_EOL.PHP_EOL.$this->getMimeEntityString($children);
        }

        return $string;
    }

    /**
     * {@inheritdoc}
     */
    public function registerPlugin(Swift_Events_EventListener $plugin)
    {
		array_push($this->plugins, $plugin);
    }

	/**
	 * @param $message
	 */
	protected function beforeSendPerformed(Swift_Mime_Message $message)
	{
		foreach ($this->plugins as $plugin) {
			$evt = new \Swift_Events_SendEvent($this, $message);
			$plugin->beforeSendPerformed($evt);
		}
	}
}
