<?php namespace Illuminate\Support\Traits;

use DateTime;
use Carbon\Carbon;

trait DurationTrait {

	/**
	 * Calculate the number of seconds with the given delay.
	 *
	 * @param  \DateTime|int  $delay
	 * @return int
	 */
	protected function getSeconds($delay)
	{
		if ($delay instanceof DateTime)
		{
			return max(0, Carbon::instance($delay)->diffInSeconds());
		}

		return (int) $delay;
	}

	/**
	 * Calculate the number of minutes with the given duration.
	 *
	 * @param  \DateTime|int  $duration
	 * @return int|null
	 */
	protected function getMinutes($duration)
	{
		if ($duration instanceof DateTime)
		{
			$fromNow = Carbon::instance($duration)->diffInMinutes();

			return $fromNow > 0 ? $fromNow : null;
		}

		return (int) $duration;
	}

}
