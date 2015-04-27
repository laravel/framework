<?php namespace Illuminate\Database\Migrations;

abstract class Migration {

	/**
	 * The name of the database connection to use.
	 *
	 * @var string
	 */
  protected $connection;

  /**
   * Activates/Deactivates the migration
   *
   * @var boolean
   */
  protected $enabled = true;

	/**
	 * Get the migration connection name.
	 *
	 * @return string
	 */
	public function getConnection()
	{
		return $this->connection;
  }

  /**
   * Get the migration activation state.
   *
   * @return boolean
   */

  public function isEnabled()
  {
    return $this->enabled;
  }

}
