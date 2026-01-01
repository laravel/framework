<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Concerns\FindsAvailableModels;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\ModelInfo;
use Illuminate\Database\Eloquent\ModelInspector;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\suggest;

#[AsCommand(name: 'model:show')]
class ShowModelCommand extends DatabaseInspectionCommand implements PromptsForMissingInput
{
    use FindsAvailableModels;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'model:show {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show information about an Eloquent model';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'model:show {model : The model to show}
                {--database= : The database connection to use}
                {--json : Output the model as JSON}';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(ModelInspector $modelInspector)
    {
        try {
            $info = $modelInspector->inspect(
                $this->argument('model'),
                $this->option('database')
            );
        } catch (BindingResolutionException $e) {
            $this->components->error($e->getMessage());

            return 1;
        }

        $this->display($info);

        return 0;
    }

    /**
     * Render the model information.
     *
     * @return void
     */
    protected function display(ModelInfo $modelData)
    {
        $this->option('json')
            ? $this->displayJson($modelData)
            : $this->displayCli($modelData);
    }

    /**
     * Render the model information as JSON.
     *
     * @return void
     */
    protected function displayJson(ModelInfo $modelData)
    {
        $this->output->writeln(
            (new Collection($modelData))->toJson()
        );
    }

    /**
     * Render the model information for the CLI.
     *
     * @return void
     */
    protected function displayCli(ModelInfo $modelData)
    {
        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>'.$modelData->class.'</>');
        $this->components->twoColumnDetail('Database', $modelData->database);
        $this->components->twoColumnDetail('Table', $modelData->table);

        if (($policy = $modelData->policy ?? false)) {
            $this->components->twoColumnDetail('Policy', $policy);
        }

        $this->newLine();

        $this->components->twoColumnDetail(
            '<fg=green;options=bold>Attributes</>',
            'type <fg=gray>/</> <fg=yellow;options=bold>cast</>',
        );

        foreach ($modelData->attributes as $attribute) {
            $first = trim(sprintf(
                '%s %s',
                $attribute['name'],
                (new Collection(['increments', 'unique', 'nullable', 'fillable', 'hidden', 'appended']))
                    ->filter(fn ($property) => $attribute[$property])
                    ->map(fn ($property) => sprintf('<fg=gray>%s</>', $property))
                    ->implode('<fg=gray>,</> ')
            ));

            $second = (new Collection([
                $attribute['type'],
                $attribute['cast'] ? '<fg=yellow;options=bold>'.$attribute['cast'].'</>' : null,
            ]))->filter()->implode(' <fg=gray>/</> ');

            $this->components->twoColumnDetail($first, $second);

            if ($attribute['default'] !== null) {
                $this->components->bulletList(
                    [sprintf('default: %s', $attribute['default'])],
                    OutputInterface::VERBOSITY_VERBOSE
                );
            }
        }

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>Relations</>');

        foreach ($modelData->relations as $relation) {
            $this->components->twoColumnDetail(
                sprintf('%s <fg=gray>%s</>', $relation['name'], $relation['type']),
                $relation['related']
            );
        }

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>Events</>');

        if ($modelData->events->count()) {
            foreach ($modelData->events as $event) {
                $this->components->twoColumnDetail(
                    sprintf('%s', $event['event']),
                    sprintf('%s', $event['class']),
                );
            }
        }

        $this->newLine();

        $this->components->twoColumnDetail('<fg=green;options=bold>Observers</>');

        if ($modelData->observers->count()) {
            foreach ($modelData->observers as $observer) {
                $this->components->twoColumnDetail(
                    sprintf('%s', $observer['event']),
                    implode(', ', $observer['observer'])
                );
            }
        }

        $this->newLine();
    }

    /**
     * Prompt for missing input arguments using the returned questions.
     *
     * @return array<string, \Closure(): string>
     */
    protected function promptForMissingArgumentsUsing(): array
    {
        return [
            'model' => fn (): string => suggest('Which model would you like to show?', $this->findAvailableModels()),
        ];
    }
}
