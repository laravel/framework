<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:seeder')]
class SeederMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:seeder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new seeder class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Seeder';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        parent::handle();

        if ($this->option('auto-register')) {
            $this->autoRegisterSeeder();
        }
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return $this->resolveStubPath('/stubs/seeder.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return is_file($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace('\\', '/', Str::replaceFirst($this->rootNamespace(), '', $name));

        if (is_dir($this->laravel->databasePath().'/seeds')) {
            return $this->laravel->databasePath().'/seeds/'.$name.'.php';
        }

        return $this->laravel->databasePath().'/seeders/'.$name.'.php';
    }

    /**
     * Get the root namespace for the class.
     *
     * @return string
     */
    protected function rootNamespace()
    {
        return 'Database\Seeders\\';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['auto-register', 'r', InputOption::VALUE_NONE, 'Auto seeder register'],
        ];
    }

    /**
     * Auto register the newly created seeder in DatabaseSeeder
     *
     * @return void
     */
    protected function autoRegisterSeeder()
    {
        $databaseSeederPath = $this->laravel->databasePath('seeders/DatabaseSeeder.php');
        $seederClassName = $this->argument('name');
        $callAddition = "            {$seederClassName}::class,";

        $content = file_get_contents($databaseSeederPath);

        if (Str::contains($content, $callAddition)) {
            $this->info("Seeder '{$seederClassName}' is already registered in DatabaseSeeder.");

            return;
        }

        // Match the closing "])" of the $this->call([...]) array
        $pattern = '/(\$this->call\(\[.*?)(\s*\])/s';

        $updatedContent = preg_replace_callback(
            $pattern,
            function ($matches) use ($callAddition) {
                return $matches[1]."\n".$callAddition.'        '.$matches[2];
            },
            $content,
            1
        );

        file_put_contents($databaseSeederPath, $updatedContent);
        $this->info("Seeder '{$seederClassName}' has been successfully registered in DatabaseSeeder.");
    }
}
