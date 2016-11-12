<?php

use Illuminate\Foundation\Console\ModelMakeCommand;

class ModelMakeCommandTest extends CommandTester
{

    /** @var  string */
    protected $commandName = 'make:model';

    /** @var  CommandTestApplication */
    protected $app;

    /** @var  string */
    protected $path;

    /** @var  FilesystemMock */
    protected $filesystem;

    public function setUp()
    {
        parent::setUp();

        $this->app = $this->makeApp();
        $this->path = $this->app->path() . '/Foo.php';
        $this->filesystem = new FilesystemMock;
    }

    public function testModelIsCreatedIfNotExistsAndDirectoryIsNotCreatedIfExists()
    {
        $this->filesystem->willCheckFile($this->path, false)
                         ->willCheckDirectory(dirname($this->path), true)
                         ->willNotMakeAnyDirectory()
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')
                         ->willPutFile($this->path, 'App;Foo');

        $output = $this->runCommand();

        $this->assertContains('Model Foo created successfully', $output);
    }

    public function testModelIsNotCreatedIfExists()
    {
        $this->filesystem->willCheckFile($this->path, true)
                         ->willNotMakeAnyDirectory()
                         ->willNotGetAnyFile()
                         ->willNotPutAnyFile();

        $output = $this->runCommand();

        $this->assertContains('Model Foo already exists', $output);
    }

    public function testDirectoryIsCreatedIfNotExists()
    {
        $this->filesystem->willCheckFile($this->path, false)
                         ->willCheckDirectory(dirname($this->path), false)
                         ->willMakeDirectory(dirname($this->path), 0777, true, true)
                         ->willGetFile('/stubs\/model\.stub/', 'DummyNamespace;DummyClass')
                         ->willPutFile($this->path, 'App;Foo');

        $output = $this->runCommand();

        $this->assertContains('Model Foo created successfully', $output);
    }

    protected function runCommand()
    {
        return parent::runCommand(
            $this->app->makeCommand(ModelMakeCommand::class, $this->filesystem->getMock())
        );
    }
}
