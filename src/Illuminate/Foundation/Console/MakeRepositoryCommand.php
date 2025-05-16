<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Pluralizer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'make:repository')]
class MakeRepositoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:repository {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Making Repository and Interface';

    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * Create a new command instance.
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * For Interface
         */
        $path = $this->getSourceFilePath();

        $this->makeDirectory(dirname($path));

        $contents = $this->getSourceFile();

        if (! $this->files->exists($path)) {
            $this->files->put($path, $contents);
            $this->info("File : {$path} created");
        } else {
            $this->warn("File : {$path} Already Exist");
        }

        /**
         * For Repository
         */
        $pathrepo = $this->getSourceFilePathRepo();

        $this->makeDirectory(dirname($pathrepo));

        $contentsrepo = $this->getSourceFileRepo();

        if (! $this->files->exists($pathrepo)) {
            $this->files->put($pathrepo, $contentsrepo);
            $this->info("File : {$pathrepo} created");
        } else {
            $this->info("File : {$pathrepo} already exits");
        }
    }

    public function getSourceFile()
    {
        return $this->getStubContents($this->getStubPath(), $this->getStubVariables());
    }

    public function getSourceFileRepo()
    {
        return $this->getStubContents($this->getStubPathRepo(), $this->getStubVariablesRepo());
    }

    public function getStubVariables()
    {
        return [
            'NAMESPACE' => 'App\\Repositories\\Interface',
            'CLASS_NAME' => $this->getSingularClassName($this->argument('name')),
        ];
    }

    public function getStubVariablesRepo()
    {
        return [
            'NAMESPACE' => 'App\\Repositories\\Repository',
            'CLASS_NAME' => $this->getSingularClassName($this->argument('name')),
        ];
    }

    public function getSingularClassName($name)
    {
        return ucwords(Pluralizer::singular($name));
    }

    public function getStubContents($stub, $stubVariables = [])
    {
        $contents = file_get_contents($stub);

        foreach ($stubVariables as $search => $replace) {
            $contents = str_replace('$' . $search . '$', $replace, $contents);
        }

        return $contents;
    }

    public function getSourceFilePath()
    {
        return app_path('Repositories/Interface/' . $this->argument('name') . 'RepositoryInterface.php');
    }

    public function getSourceFilePathRepo()
    {
        return app_path('Repositories/Repository/' . $this->argument('name') . 'Repository.php');
    }

    public function getStubPath()
    {
        return __DIR__ . '/stubs/repository-interface.stub';
    }

    public function getStubPathRepo()
    {
        return __DIR__ . '/stubs/repository.stub';
    }

    protected function makeDirectory($path): string
    {
        if (! $this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true, true);
        }

        return $path;
    }

    public function getSignature(): string
    {
        return $this->signature;
    }
}
