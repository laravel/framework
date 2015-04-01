<?php namespace Illuminate\Contracts\Database;

interface SoftDeletes {

	/**
	 * Boot soft deletes for the model.
	 * 
	 * @return void
	 */
	public static function bootSoftDeletes();	

	/**
	 * Force a hard delete on a soft deleted model.
	 *
	 * @return void
	 */
	public function forceDelete();

	/**
	 * Restore a soft-deleted model instance.
	 *
	 * @return bool|null
	 */
	public function restore();

	/**
	 * Determine if the model instance has been soft-deleted.
	 *
	 * @return bool
	 */
	public function trashed();

	/**
	 * Get the name of the "deleted at" column.
	 *
	 * @return string
	 */
	public function getDeletedAtColumn();

	/**
	 * Get the fully qualified "deleted at" column.
	 *
	 * @return string
	 */
	public function getQualifiedDeletedAtColumn();

}
