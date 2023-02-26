<?php

namespace Illuminate\Database\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ComposerJson;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Terminal;

#[AsCommand(name: 'model:list')]
class ListModelsCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lists Eloquent Models';

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'model:list {--folder=}';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $models = $this->getModelsLists();

        $data = $this->inspectModels($models);

        $this->printList($data);
    }

    protected function getModelsLists()
    {
        $filterModels = function ($classFilePath, $namespacedClassName) {
            $reflection = new ReflectionClass($namespacedClassName);

            return $reflection->isSubclassOf(Model::class);
        };

        $folder = ltrim($this->option('folder'), '=');
        $folder = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $folder);
        $pathFilter = $folder ? fn ($path, $relativePath) => str_contains($relativePath, $folder) : null;

        return ComposerJson::make(base_path())->getClasslists($filterModels, $pathFilter);
    }

    protected function inspectModels($classLists)
    {
        $models = [];
        foreach ($classLists as $path => $classList) {
            $models[$path] = [];
            foreach ($classList as $list) {
                foreach ($list as $class) {
                    $classPath = $class['currentNamespace'];
                    $table = (new ReflectionClass($classPath))->newInstanceWithoutConstructor()->getTable();
                    $models[$path][] = [
                        'table' => $table,
                        'class' => $classPath,
                        'relativePath' => $class['relativePath'],
                    ];
                }
            }
        }

        return $models;
    }

    protected function printList($modelsLists)
    {
        $output = $this->getOutput();
        foreach ($modelsLists as $path => $modelsList) {
            $output->writeln(' - '.$path.'composer.json');
            foreach ($modelsList as $model) {
                $output->writeln('    <fg=yellow>'.$model['class'].'</>   (<fg=blue>\''.$model['table'].'\'</>)');
                $output->writeln('<fg=gray>'.str_repeat('_', (new Terminal())->getWidth()).'</>');
            }
        }
    }
}
