<?php

namespace Illuminate\Notifications\Messages;

use Closure;

class SMSMessage
{
	/**
	 * The text content of the message.
	 *
	 * @var string
	 */
	public $content;
	
	/**
	 * The message's to number.
	 *
	 * @var array
	 */
	public $to;
	
	/**
	 * Set the content of the Slack message.
	 *
	 * @param  string  $content
	 * @return $this
	 */
	public function content($content)
	{
		$this->content = $content;
		return $this;
	}
	
	/**
	 * Set the content of the Slack message.
	 *
	 * @param  string  $to
	 * @return $this
	 */
	public function to($to)
	{
		$this->to = $to;
		return $this;
	}
}
