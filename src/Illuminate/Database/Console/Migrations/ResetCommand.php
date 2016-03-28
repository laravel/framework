<?php

namespace Illuminate\Database\Console\Migrations;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Input\InputOption;

class ResetCommand extends Command
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'migrate:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rollback all database migrations';

    /**
     * The migrator instance.
     *
     * @var \Illuminate\Database\Migrations\Migrator
     */
    protected $migrator;

    /**
     * Create a new migration rollback command instance.
     *
     * @param  \Illuminate\Database\Migrations\Migrator  $migrator
     * @return void
     */
    public function __construct(Migrator $migrator)
    {
        parent::__construct();

        $this->migrator = $migrator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $this->migrator->setConnection($this->input->getOption('database'));

        if (! $this->migrator->repositoryExists()) {
            $this->output->writeln('<comment>Migration table not found.</comment>');

            return;
        }

        if ($this->input->getOption('tags')) {
            // If the --tags option is provided loop through all tags and re-run the command with --tag=xxx instead
            $tags = $this->migrator->getAllTags();

            foreach ($tags as $tag => $path) {
                $this->output->writeln('Resetting Migrations for tag: '.$tag);

                $this->call('migrate:reset', [
                    '--pretend' => $this->input->getOption('pretend'),
                    '--database' => $this->input->getOption('database'),
                    '--force' => $this->input->getOption('force'),
                    '--tag' => $tag,
                ]);
            }

            $this->output->writeln('Resetting Core Migrations');

            // Then call the default tag
            $this->call('migrate:reset', [
                '--pretend' => $this->input->getOption('pretend'),
                '--database' => $this->input->getOption('database'),
                '--force' => $this->input->getOption('force'),
            ]);

            // Finally return, we don't do anything else
            return;
        }

        $pretend = $this->input->getOption('pretend');

        // Fetch the tag this command is run against, can be empty thats not an issue
        // its by design meaning "core" migrations are run with an empty tag
        $tag = $this->input->getOption('tag');

        $this->migrator->reset($pretend, $tag);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note) {
            $this->output->writeln($note);
        }
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'],

            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],

            ['pretend', null, InputOption::VALUE_NONE, 'Dump the SQL queries that would be run.'],

            ['tag', null, InputOption::VALUE_OPTIONAL, 'Reset migrations for a specific tag.'],

            ['tags', null, InputOption::VALUE_NONE, 'Reset migrations for all tags.'],
        ];
    }
}
