<?php

namespace Illuminate\Database\Migrations;

use Closure;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;

class MigrationCreator
{
    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The registered post create hooks.
     *
     * @var array
     */
    protected $postCreate = [];

    /**
     * Create a new migration creator instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @return void
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;
    }

    /**
     * Create a new migration at the given path.
     *
     * @param  string  $name
     * @param  string  $path
     * @param  string  $table
     * @param  bool    $create
     * @return string
     * @throws \Exception
     */
    public function create($name, $path, $table = null, $create = false)
    {
        $this->ensureMigrationDoesntAlreadyExist($name);

        // First we will get the file path for the migration and generate the contents
        // dynamically based on the arguments provided by the developer. Once we
        // have these we will save the file and fire the post create event.
        $path = $this->getPath($name, $path);
        $content = $this->getContent($name, $table, $create);

        $this->files->put($path, $content);

        // Next, we will fire any hooks that are supposed to fire after a migration is
        // created. Once that is done we'll be ready to return the full path to the
        // migration file so it can be used however it's needed by the developer.
        $this->firePostCreateHooks();

        return $path;
    }

    /**
     * Ensure that a migration with the given name does not already exist.
     *
     * @param  string  $name
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function ensureMigrationDoesntAlreadyExist($name)
    {
        if (class_exists($className = $this->getClassName($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    /**
     * Generate the content for the migration file.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getContent($name, $table, $create)
    {
        $stub = $this->getStub($name, $table, $create);
        $placeholders = $this->getPlaceholders($name, $table);

        // Here we will replace the any place-holders with the values specified by
        // the developer, which is useful for quickly creating a tables creation
        // or update migration from the console instead of typing it manually.

        return $this->populateStub($stub, $placeholders);
    }

    /**
     * Get the migration stub file.
     *
     * @param  string  $name
     * @param  string  $table
     * @param  bool    $create
     * @return string
     */
    protected function getStub($name, $table, $create)
    {
        // We also have stubs for creating, dropping, renaming tables as well as
        // adding, removing, or renaming columns. This saves the developer
        // some typing when they making migrations.

        if ($create) {
            return $this->files->get($this->stubPath().'/create.stub');
        }

        if ($this->nameFollowsConvention($name)) {
            return $this->files->get($this->stubPath().'/'.$this->extractStubFromName($name));
        }

        if (! is_null($table)) {
            return $this->files->get($this->stubPath().'/update.stub');
        }

        return $this->files->get($this->stubPath().'/blank.stub');
    }

    /**
     * Populate the place-holders in the migration stub.
     *
     * @param  string  $stub
     * @param  array  $placeholders
     * @return string
     */
    protected function populateStub($stub, array $placeholders)
    {
        return str_replace(array_keys($placeholders), $placeholders, $stub);
    }

    /**
     * Determine the place-holders and values for the migration stub.
     *
     * @param  string  $name
     * @param  string  $table
     * @return array
     */
    protected function getPlaceholders($name, $table)
    {
        $placeholders = [
            'DummyClass' => $this->getClassName($name),
        ];

        if ($this->nameFollowsConvention($name)) {
            $placeholders += $this->extractPlaceholderValuesFromName($name);
        }

        if (! is_null($table)) {
            $placeholders['DummyTable'] = $table;
        }

        return $placeholders;
    }

    /**
     * Get the class name of a migration name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getClassName($name)
    {
        return Str::studly($name);
    }

    /**
     * Get the full path to the migration.
     *
     * @param  string  $name
     * @param  string  $path
     * @return string
     */
    protected function getPath($name, $path)
    {
        return $path.'/'.$this->getDatePrefix().'_'.$name.'.php';
    }

    /**
     * Does the migration name follow one of the convenient naming conventions.
     *
     * @param  string  $name
     * @return bool
     */
    protected function nameFollowsConvention($name)
    {
        return preg_match('/(create|drop)_\w+/', $name)
            || preg_match('/(rename|add)_\w+_to_\w+/', $name)
            || preg_match('/remove_\w+_from_\w+/', $name);
    }

    /**
     * Extract the stub file name from the migration name.
     *
     * @param  string  $name
     * @return bool
     */
    protected function extractStubFromName($name)
    {
        $stub = Str::before($name, '_');

        if ($stub === 'rename') {
            $stub = Str::contains($name, '_in_') ? 'rename-column' : 'rename-table';
        }

        return $stub.'.stub';
    }

    /**
     * Extract the place-holders from the migration name.
     *
     * @param  string  $name
     * @return array
     */
    protected function extractPlaceholderValuesFromName($name)
    {
        $patterns = [
            'create_(?P<DummyTable>\w+)',
            'drop_(?P<DummyTable>\w+)',
            'rename_(?P<DummyColumnFrom>\w+)_to_(?P<DummyColumnTo>\w+)_in_(?P<DummyTable>\w+)',
            'rename_(?P<DummyTableFrom>\w+)_to_(?P<DummyTableTo>\w+)',
            'add_(?P<DummyColumn>\w+)_to_(?P<DummyTable>\w+)',
            'remove_(?P<DummyColumn>\w+)_from_(?P<DummyTable>\w+)',
        ];

        preg_match('/'.implode('|', $patterns).'/J', $name, $matches);

        $placeholders = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

        array_walk($placeholders, function (&$value, $key) {
            if (Str::startsWith($key, 'DummyTable') && Str::endsWith($value, '_table')) {
                $value = Str::replaceLast('_table', '', $value);
            }
        });

        return array_filter($placeholders);
    }

    /**
     * Fire the registered post create hooks.
     *
     * @return void
     */
    protected function firePostCreateHooks()
    {
        foreach ($this->postCreate as $callback) {
            call_user_func($callback);
        }
    }

    /**
     * Register a post migration create hook.
     *
     * @param  \Closure  $callback
     * @return void
     */
    public function afterCreate(Closure $callback)
    {
        $this->postCreate[] = $callback;
    }

    /**
     * Get the date prefix for the migration.
     *
     * @return string
     */
    protected function getDatePrefix()
    {
        return date('Y_m_d_His');
    }

    /**
     * Get the path to the stubs.
     *
     * @return string
     */
    public function stubPath()
    {
        return __DIR__.'/stubs';
    }

    /**
     * Get the filesystem instance.
     *
     * @return \Illuminate\Filesystem\Filesystem
     */
    public function getFilesystem()
    {
        return $this->files;
    }
}
