<?php

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Mockery as m;

abstract class CommandTester extends PHPUnit_Framework_TestCase
{

    /** @var  string */
    protected $commandClass;

    /** @var  CommandTestApplication */
    protected $app;

    /** @var  Command */
    protected $command = null;

    public function setUp()
    {
        parent::setUp();

        $this->app = $this->makeApp();
    }

    public function tearDown()
    {
        m::close();
    }

    /**
     * @return $this
     */
    protected function makeCommand()
    {
        $this->command = m::mock($this->commandClass.'[call]', func_get_args());

        $this->command->setLaravel($this->app);

        return $this;
    }

    /**
     * Execute the command and return the output.
     *
     * @param  array  $arguments
     * @return string
     */
    protected function runCommand($arguments)
    {
        if(!$this->command) {
            $this->makeCommand();
        }

        $input = new ArrayInput($arguments);
        $output = new BufferedOutput();

        $this->command->run($input, $output);

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

    /** @var  \Mockery\Expectation */
    protected $lastExpectation = null;

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
        $this->lastExpectation = $this->mock
            ->shouldReceive('exists')
            //->once()
            ->with($file)
            ->andReturn($found);

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
        $this->lastExpectation = $this->mock->shouldNotReceive('exists')->with($file);

        return $this;
    }

    /**
     * Do not expect any file existence checking.
     *
     * @return $this
     */
    public function willNotCheckAnyFile()
    {
        $this->lastExpectation = $this->mock->shouldNotReceive('exists')->with(m::any());

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
        $this->lastExpectation = $this->mock
            ->shouldReceive('isDirectory')
            //->once()
            ->with($directory)
            ->andReturn($found);

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
        $this->lastExpectation = $this->mock->shouldNotReceive('isDirectory')->with($directory);

        return $this;
    }

    /**
     * Do not expect any directory existence checking.
     *
     * @return $this
     */
    public function willNotCheckAnyDirectory()
    {
        $this->lastExpectation = $this->mock->shouldReceive('isDirectory')->with(m::any());

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
        $this->lastExpectation = $this->mock
            ->shouldReceive('makeDirectory')
            //->once()
            ->with($path, $mode, $recursive, $force)
            ->andReturn(true);

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
        $this->lastExpectation = $this->mock->shouldNotReceive('makeDirectory')->with($path, $mode, $recursive, $force);

        return $this;
    }

    /**
     * Do not expect any directory creation.
     *
     * @return $this
     */
    public function willNotMakeAnyDirectory()
    {
        $this->lastExpectation = $this->mock->shouldNotReceive('makeDirectory')->with(m::any());

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
        $this->lastExpectation = $this->mock
            ->shouldReceive('get')
            //->once()
            ->with($file)
            ->andReturn($content);

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
        $this->lastExpectation = $this->mock->shouldNotReceive('get')->with($file);

        return $this;
    }

    /**
     * Do not expect any file retrieval.
     *
     * @return $this
     */
    public function willNotGetAnyFile()
    {
        $this->lastExpectation = $this->mock->shouldNotReceive('get')->with(m::any());

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
        $this->lastExpectation = $this->mock
            ->shouldReceive('put')
            //->once()
            ->with($file, $content ?: m::any());

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
        $this->lastExpectation = $this->mock->shouldNotReceive('put')->with($file, $content ?: m::any());

        return $this;
    }

    /**
     * Do not expect any file to be saved.
     *
     * @return $this
     */
    public function willNotPutAnyFile()
    {
        $this->lastExpectation = $this->mock->shouldNotReceive('put')->with(m::any());

        return $this;
    }

    public function __call($name, $arguments)
    {
        if(!$this->lastExpectation) {
            trigger_error('Call to undefined method '.__CLASS__.'::'.$name.'()', E_USER_ERROR);
        }

        $this->lastExpectation = call_user_func_array([$this->lastExpectation, $name], $arguments);

        return $this;
    }
}

class CommandTestApplication extends Application
{

    public function getNamespace()
    {
        return 'App\\';
    }
}