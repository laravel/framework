<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Mockery\Matcher\MatcherAbstract;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Mockery as m;

abstract class CommandTester extends PHPUnit_Framework_TestCase
{

    protected $commandName = 'command:name';

    public function tearDown()
    {
        m::close();
    }

    protected function runCommand($command)
    {
        $input = new ArgvInput([$this->commandName, 'Foo']);
        $output = new BufferedOutput();

        $command->run($input, $output);

        return $output->fetch();
    }

    protected function makeApp()
    {
        return new CommandTestApplication('/');
    }
}

class FilesystemMock
{

    /** @var  \Mockery\Mock */
    protected $mock;

    /**
     * Create a default mock and store it on the instance.
     *
     * Add mocked methods later with calls to will* methods.
     */
    public function __construct()
    {
        $this->mock = m::mock(Filesystem::class);
    }

    /**
     * Defer all the missing methods and return the generated mock.
     *
     * @return Mockery\Mock
     */
    public function getMock()
    {
        return $this->mock->makePartial();
    }

    /**
     * Expect the file existence to be checked.
     *
     * @param  string  $file
     * @param  boolean $found
     * @return $this
     */
    public function willCheckFile($file, $found)
    {
        $this->mock->shouldReceive('exists')->with($file)->andReturn($found);

        return $this;
    }

    /**
     * Do not expect the file existence to be checked.
     *
     * @param  string  $file
     * @return $this
     */
    public function willNotCheckFile($file)
    {
        $this->mock->shouldNotReceive('exists')->with($file);

        return $this;
    }

    /**
     * Do not expect any file existence checking.
     *
     * @return $this
     */
    public function willNotCheckAnyFile()
    {
        $this->mock->shouldNotReceive('exists')->with(m::any());

        return $this;
    }

    /**
     * Expect the directory existence to be checked.
     *
     * @param  string  $directory
     * @param  boolean $found
     * @return $this
     */
    public function willCheckDirectory($directory, $found)
    {
        $this->mock->shouldReceive('isDirectory')->with($directory)->andReturn($found);

        return $this;
    }

    /**
     * Do not expect the directory existence to be checked.
     *
     * @param  string  $directory
     * @return $this
     */
    public function willNotCheckDirectory($directory)
    {
        $this->mock->shouldNotReceive('isDirectory')->with($directory);

        return $this;
    }

    /**
     * Do not expect any directory existence checking.
     *
     * @return $this
     */
    public function willNotCheckAnyDirectory()
    {
        $this->mock->shouldReceive('isDirectory')->with(m::any());

        return $this;
    }

    /**
     * Expect the directory to be created.
     *
     * @param  string  $path
     * @param  int     $mode
     * @param  bool    $recursive
     * @param  bool    $force
     * @return $this
     */
    public function willMakeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        $this->mock->shouldReceive('makeDirectory')->with($path, $mode, $recursive, $force)->andReturn(true);

        return $this;
    }

    /**
     * Do not expect the directory to be created.
     *
     * @param  string  $path
     * @param  int     $mode
     * @param  bool    $recursive
     * @param  bool    $force
     * @return $this
     */
    public function willNotMakeDirectory($path, $mode = 0755, $recursive = false, $force = false)
    {
        $this->mock->shouldNotReceive('makeDirectory')->with($path, $mode, $recursive, $force);

        return $this;
    }

    /**
     * Do not expect any directory creation.
     *
     * @return $this
     */
    public function willNotMakeAnyDirectory()
    {
        $this->mock->shouldNotReceive('makeDirectory')->with(m::any());

        return $this;
    }

    /**
     * Expect the file to be retrieved.
     *
     * @param  string  $file
     * @param  string  $content
     * @return $this
     */
    public function willGetFile($file, $content)
    {
        $this->mock->shouldReceive('get')->with($file)->andReturn($content);

        return $this;
    }

    /**
     * Do not expect the file to be retrieved.
     *
     * @param  string  $file
     * @return $this
     */
    public function willNotGetFile($file)
    {
        $this->mock->shouldNotReceive('get')->with($file);

        return $this;
    }

    /**
     * Do not expect any file retrieval.
     *
     * @return $this
     */
    public function willNotGetAnyFile()
    {
        $this->mock->shouldNotReceive('get')->with(m::any());

        return $this;
    }

    /**
     * Expect the file to be saved.
     *
     * @param  string  $file
     * @param  string  $content
     * @return $this
     */
    public function willPutFile($file, $content = null)
    {
        $this->mock->shouldReceive('put')->with($file, $content ?: m::any());

        return $this;
    }

    /**
     * Do not expect the file to be saved.
     *
     * @param  string  $file
     * @param  string  $content
     * @return $this
     */
    public function willNotPutFile($file, $content)
    {
        $this->mock->shouldNotReceive('put')->with($file, $content ?: m::any());

        return $this;
    }

    /**
     * Do not expect any file to be saved.
     *
     * @return $this
     */
    public function willNotPutAnyFile()
    {
        $this->mock->shouldNotReceive('put')->with(m::any());

        return $this;
    }
}

class CommandTestApplication extends Application
{

    public function getNamespace()
    {
        return 'App\\';
    }

    public function makeCommand($command)
    {
        $dependencies = array_slice(func_get_args(), 1);

        $command = call_user_func_array(
            [new ReflectionClass($command), 'newInstance'],
            $dependencies
        );

        $command->setLaravel($this);

        return $command;
    }
}