<?php

namespace Illuminate\Auth\Access;

abstract class Policy
{
	/**
	 * The authenticated user.
	 *
	 * @var \Illuminate\Contracts\Auth\Authenticatable
	 */
	protected $user;

	/**
	 * Set the user for the policy.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $user
	 * @return $this
	 */
	public function setUser($user)
	{
		$this->user = $user;

		return $this;
	}
}
