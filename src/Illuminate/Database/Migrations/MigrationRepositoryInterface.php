<?php

namespace Illuminate\Database\Migrations;

interface MigrationRepositoryInterface
{
    /**
     * Get the ran migrations for a given package.
     *
     * @param  string $tag
     * @return array
     */
    public function getRan($tag = '');

    /**
     * Get the last migration batch.
     *
     * @param  string $tag
     * @return array
     */
    public function getLast($tag = '');

    /**
     * Log that a migration was run.
     *
     * @param  string  $file
     * @param  int     $batch
     * @param  string $tag
     * @return void
     */
    public function log($file, $batch, $tag = '');

    /**
     * Remove a migration from the log.
     *
     * @param  object  $migration
     * @param  string $tag
     * @return void
     */
    public function delete($migration, $tag = '');

    /**
     * Get the next migration batch number.
     *
     * @param  string $tag
     * @return int
     */
    public function getNextBatchNumber($tag = '');

    /**
     * Create the migration repository data store.
     *
     * @return void
     */
    public function createRepository();

    /**
     * Determine if the migration repository exists.
     *
     * @return bool
     */
    public function repositoryExists();

    /**
     * Determine if the migration repository has the tag column
     *
     * @return bool
     */
    public function repositoryTagColumnExists();

    /**
     * Add the tags column if needed
     *
     * @return void
     */
    public function addTagColumn();

    /**
     * Set the information source to gather data.
     *
     * @param  string  $name
     * @return void
     */
    public function setSource($name);
}
