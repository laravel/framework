<?php

namespace Illuminate\Database\Console\Upsertions;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

abstract class BaseCommand extends Command
{
    /**
     * The upsertion resposity.
     *
     * @var Illuminate\Database\Console\Upsertions\UpsertionRepository
     */
    protected $repository;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Create a new base upsertion command instance.
     *
     * @param  Illuminate\Database\Console\Upsertions\UpsertionRepository  $upsertionRepository
     * @param  Illuminate\Filesystem\Filesystem  $filesystem
     * @return void
     */
    public function __construct(UpsertionRepository $upsertionRepository, Filesystem $filesystem)
    {
        parent::__construct();

        $this->repository = $upsertionRepository;
        $this->filesystem = $filesystem;
    }

    /**
     * Initialize the command flow.
     *
     * @return void
     */
    public function initializeCommandFlow()
    {
        if ($this->option('all')) {
            $this->selectAllUpserts(false);
        } else {
            $this->selectFunctionality();
        }
    }

    /**
     * Render the initial selecting functionality.
     *
     * @return void
     */
    private function selectFunctionality()
    {
        $functionality = select(
            label: 'What do you want to do?',
            options: [
                'all' => 'Upsert all found files',
                'selected' => 'Upsert only selected files',
            ]
        );

        switch ($functionality) {
            case 'selected':
                $this->selectUpserts();

                break;
            case 'all':
                $this->selectAllUpserts();
                break;
        }
    }

    /**
     * Render the multisearch to select which upsert files to run.
     *
     * @return void
     */
    private function selectUpserts()
    {
        $allUpserters = $this->repository->foundUpsertions;
        $foundUpserterCount = count($allUpserters);

        if ($foundUpserterCount < 1) {
            return;
        }

        $files = $this->repository->getClassNames($allUpserters);

        $selectedFilePaths = multisearch(
            label: 'Search for the files you want to upsert.',
            placeholder: 'E.g. UpsertUserPermissions.php',
            options: fn (string $value) => strlen($value) > 0
                ? $files
                : [],
            scroll: 10,
            required: 'You must select at least one file.',
            hint: 'Use space bar to select options. Use enter to continue'
        );

        $filesToUpsert = $this->repository->getFilesByPath($selectedFilePaths);
        $this->repository->filesToUpsert = $filesToUpsert;
        $this->runUpsertions();
    }

    /**
     * Render the initial select for all upsert files.
     *
     * @param  bool  $shouldShowSelect
     *
     * @return void
     */
    private function selectAllUpserts($shouldShowSelect = true)
    {
        $allUpserters = $this->repository->foundUpsertions;
        $foundUpserterCount = count($allUpserters);

        if ($foundUpserterCount < 1) {
            return;
        }

        $this->repository->filesToUpsert = $allUpserters;
        $files = $this->repository->getClassNames($allUpserters);

        intro("Found {$foundUpserterCount} upsert files");

        if ($shouldShowSelect) {
            $shouldStart = select(
                label: 'Would you like to upsert these files?',
                options: [
                    'Yes',
                    'No',
                    'Show me which files',
                ],
                default: 'No'
            );

            switch ($shouldStart) {
                case 'Yes':
                    $this->runUpsertions();

                    break;
                case 'No':
                    break;
                case 'Show me which files':
                    $this->showUpsertFiles($files);
                    break;
            }
        } else {
            $this->runUpsertions();
        }
    }

    /**
     * Render the found upsert files table and a select to run upserts.
     *
     * @return void
     */
    private function showUpsertFiles($files)
    {
        info('I\'ve found these upserters');

        $tableArr = array_map(function ($file) {
            return [$file];
        }, $files);

        table(
            ['Upserter Name'],
            $tableArr
        );

        $shouldStart = select(
            label: 'Would you like to upsert these files?',
            options: [
                'Yes',
                'No',
            ],
            default: 'Yes'
        );

        switch ($shouldStart) {
            case 'Yes':
                $this->runUpsertions();

                break;
            case 'No':
                break;
        }
    }

    /**
     * The abstract function to run upsert files.
     *
     * @return void
     */
    abstract public function runUpsertions();
}
