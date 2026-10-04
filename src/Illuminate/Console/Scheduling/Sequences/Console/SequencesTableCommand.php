<?php

namespace Illuminate\Console\Scheduling\Sequences\Console;

use Illuminate\Console\MigrationGeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:scheduled-sequences-table')]
class SequencesTableCommand extends MigrationGeneratorCommand
{
    protected $signature = 'make:scheduled-sequences-table';

    protected $description = 'Create a migration for the sequence, occurrence, and outbox tables';

    protected function migrationTableName()
    {
        return 'scheduled_sequences';
    }

    protected function migrationStubFile()
    {
        return __DIR__.'/stubs/sequences.stub';
    }
}
