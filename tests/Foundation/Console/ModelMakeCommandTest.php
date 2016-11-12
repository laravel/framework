<?php

use Illuminate\Foundation\Console\ModelMakeCommand;

class ModelMakeCommandTest extends CommandTester
{
    /** @var string */
    protected $path;

    /** @var FilesystemMock */
    protected $filesystem;

    public function setUp()
    {
        parent::setUp();

        $this->commandClass = ModelMakeCommand::class;

        $this->path = $this->app->path().'/Foo.php';
        $this->filesystem = new FilesystemMock;
    }

    public function testModelIsCreatedIfNotExistsAndDirectoryIsNotCreatedIfExists()
    {
        $this->filesystem->willCheckFile($this->path, false)->once()
                         ->willCheckDirectory(dirname($this->path), true)->once()
                         ->willNotMakeAnyDirectory()
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')->once()
                         ->willPutFile($this->path, 'App;Foo')->once();

        $output = $this->runCommand(['name' => 'Foo']);

        $this->assertContains('Model created successfully', $output);
    }

    public function testModelIsNotCreatedIfExists()
    {
        $this->filesystem->willCheckFile($this->path, true)->once()
                         ->willNotMakeAnyDirectory()
                         ->willNotGetAnyFile()
                         ->willNotPutAnyFile();

        $output = $this->runCommand(['name' => 'Foo']);

        $this->assertContains('Model already exists', $output);
    }

    public function testDirectoryIsCreatedIfNotExists()
    {
        $this->filesystem->willCheckFile($this->path, false)->once()
                         ->willCheckDirectory(dirname($this->path), false)->once()
                         ->willMakeDirectory(dirname($this->path), 0777, true, true)->once()
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')->once()
                         ->willPutFile($this->path, 'App;Foo')->once();

        $output = $this->runCommand(['name' => 'Foo']);

        $this->assertContains('Model created successfully', $output);
    }

    public function testMigrationIsCreatedIfRequired()
    {
        $this->filesystem->willCheckFile($this->path, false)->once()
                         ->willCheckDirectory(dirname($this->path), true)->once()
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')->once()
                         ->willPutFile($this->path, 'App;Foo')->once();

        $this->makeCommand();

        $this->command->shouldReceive('call')->with('make:migration', [
            'name' => 'create_foos_table',
            '--create' => 'foos',
        ])->once();

        $output = $this->runCommand([
            'name'        => 'Foo',
            '--migration' => true,
        ]);

        $this->assertContains('Model created successfully', $output);
    }

    public function testControllerIsCreatedIfRequired()
    {
        $this->filesystem->willCheckFile($this->path, false)
                         ->willCheckDirectory(dirname($this->path), true)
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')
                         ->willPutFile($this->path, 'App;Foo');

        $this->makeCommand();

        $this->command->shouldReceive('call')->with('make:controller', [
            'name' => 'FooController',
            '--resource' => false,
        ])->once();

        $output = $this->runCommand([
            'name'         => 'Foo',
            '--controller' => true,
            '--resource'   => false,
        ]);

        $this->assertContains('Model created successfully', $output);
    }

    public function testResourceControllerIsCreatedIfRequired()
    {
        $this->filesystem->willCheckFile($this->path, false)
                         ->willCheckDirectory(dirname($this->path), true)
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')
                         ->willPutFile($this->path, 'App;Foo');

        $this->makeCommand();

        $this->command->shouldReceive('call')->with('make:controller', [
            'name' => 'FooController',
            '--resource' => true,
        ])->once();

        $output = $this->runCommand([
            'name'         => 'Foo',
            '--controller' => true,
            '--resource'   => true,
        ]);

        $this->assertContains('Model created successfully', $output);
    }

    protected function makeCommand()
    {
        return parent::makeCommand($this->filesystem->getMock());
    }
}
