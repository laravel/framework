<?php namespace Illuminate\Queue;

interface PushQueueInterface {

	/**
	 * Subscribe a queue to the endpoint url
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @param array   $options
	 * @return array
	 */
	public function subscribe($queue, $endpoint, array $options = array());

	/**
	 * Unsubscribe a queue from an endpoint url
	 *
	 * @param string  $queue
	 * @param string  $endpoint
	 * @return array
	 */
	public function unsubscribe($queue, $endpoint);

}
