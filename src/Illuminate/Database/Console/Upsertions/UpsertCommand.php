<?php

namespace Illuminate\Database\Console\Upsertions;

use function Laravel\Prompts\outro;
use function Laravel\Prompts\progress;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'upsert')]
class UpsertCommand extends BaseCommand
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'upsert
                {--all : Run all upsert files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make sure essential seeders have been executed after something has updated';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->initializeCommandFlow();
    }

    /**
     * Run all found upsertion files.
     *
     * @return void
     */
    public function runUpsertions()
    {
        $files = $this->repository->filesToUpsert;
        $count = count($files);
        $skippedCount = 0;

        /** @var Progress<TSteps> */
        $progress = progress(
            label: 'Running upserters',
            steps: $files,
        );

        $progress->start();

        foreach ($files as $file) {
            $className = $file->getRelativePathname();
            $progress->hint("Running upserter: {$className}");
            $upserter = include $file->getRealPath();

            if (! $upserter->shouldRun()) {
                $progress->advance();
                $skippedCount++;

                continue;
            }

            $upserter->run();
            $progress->advance();
        }

        $progress->finish();

        $finishedStr = "Finished running {$count} upserters";
        $skippedStr = "skipped {$skippedCount} upserters";

        $outroStr = $skippedCount > 0
            ? $finishedStr.', '.$skippedStr
            : $finishedStr;

        outro($outroStr);
    }
}
