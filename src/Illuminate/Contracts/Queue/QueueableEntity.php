<?php namespace Illuminate\Contracts\Queue;

interface QueueableEntity {

	/**
	 * Get the queueable identity for the entity.
	 *
	 * @var mixed
	 */
	public function getQueueableId();

}
