<?php

namespace Illuminate\Database\Console\Upsertions;

use Illuminate\Console\Command;
use Illuminate\Database\Upsertions\UpsertionCreator;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\outro;
use function Laravel\Prompts\text;

#[AsCommand(name: 'make:upsertion')]
class UpsertionMakeCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:upsertion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new upsertion file';

    /**
     * The upsertion creator instance.
     *
     * @var \Illuminate\Database\Upsertions\UpsertionCreator
     */
    protected $creator;

    /**
     * Create a new make upsertion command instance.
     *
     * @param  \Illuminate\Database\Upsertions\UpsertionCreator  $upsertionCreator
     * @return void
     */
    public function __construct(UpsertionCreator $upsertionCreator)
    {
        parent::__construct();

        $this->creator = $upsertionCreator;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = text(
            label: 'Enter the name of the new upsertion',
            placeholder: 'E.g. UpsertUserPermissions',
            required: true,
            validate: fn (string $value) => match (true) {
                strlen($value) < 3 => 'The name must be at least 3 characters.',
                strlen($value) > 50 => 'The name must not exceed 50 characters.',
                $this->creator->ensureUpsertionDoesntAlreadyExist($value) => "A {$value} upsertion already exists.",
                default => null
            }
        );

        $isDone = $this->createUpsertion($name);

        if ($isDone) {
            outro("Upsertion {$name} has successfully been created.");
        }
    }

    /**
     * Create a new upsertion.
     *
     * @return string
     */
    private function createUpsertion($name)
    {
        return $this->creator->create($name);
    }
}
