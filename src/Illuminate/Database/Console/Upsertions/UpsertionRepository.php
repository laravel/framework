<?php

namespace Illuminate\Database\Console\Upsertions;

use Symfony\Component\Finder\Finder;

class UpsertionRepository
{
    /**
     * The found upserter files inside the configured directory.
     *
     * @var SplFileInfo[]
     */
    public $foundUpsertions = [];

    /**
     * The selected files to upsert.
     *
     * @var SplFileInfo[]
     */
    public $filesToUpsert = [];

    /**
     * Create a new upsertion repository instance.
     *
     * @return void
     */
    public function __construct()
    {
        foreach ((new Finder)->in(database_path('/upsertions'))->files() as $file) {
            $this->foundUpsertions[] = $file;
        }
    }

    /**
     * Get the classnames of files as an associative array.
     *
     * @param  SplFileInfo[]  $files
     * @return array
     */
    public function getClassNames($files)
    {
        $classNames = [];

        foreach ($files as $file) {
            $className = str_replace('.php', '', $file->getRelativePathname());
            $classNames[$file->getPathName()] = $className;
        }

        return $classNames;
    }

    /**
     * Get files by path.
     *
     * @param  string[]  $filePaths
     * @return SplFileInfo[]
     */
    public function getFilesByPath($filePaths)
    {
        $files = [];

        foreach ((new Finder)->in(database_path('/upsertions'))->files() as $file) {
            if (in_array($file->getPathname(), $filePaths)) {
                $files[] = $file;
            }
        }

        return $files;
    }
}
