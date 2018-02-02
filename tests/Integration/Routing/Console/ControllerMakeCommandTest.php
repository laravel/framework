<?php

namespace Illuminate\Tests\Integration\Routing\Console;

use Illuminate\Filesystem\Filesystem;
use Orchestra\Testbench\TestCase;

/**
 * @group integration
 */
class ControllerMakeCommandTest extends TestCase
{

	/**
	 * @var Filesystem
	 */
	protected $fs;

	/**
	 * @var string
	 */
	protected $controllerPath;

	protected function setUp ()
	{
		parent::setUp();
		$this->controllerPath = $this->app->basePath('app/Http/Controllers');
		$this->fs = new Filesystem;
	}

	/**
	 * Assert the contents of two files equal each other
	 *
	 * @param string $assertingFile
	 * @param string $assertingAgainst
	 *
	 * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
	 */
	protected function assertFileContents (string $assertingFile, string $assertingAgainst)
	{
		$this->assertEquals($this->fs->get($assertingFile), $this->fs->get($assertingAgainst));
	}

	/**
	 * @test
	 */
	public function test_basic_controller_make_command ()
	{
		$assertingFile = $this->controllerPath . '/BasicController.php';
		$assertingAgainst = __DIR__ . '/Assertions/BasicController.php.assertion';

		$this->artisan('make:controller', [
			'name' => 'BasicController',
			'--no-interaction' => TRUE
		]);

		$this->assertFileContents($assertingFile, $assertingAgainst);
		$this->fs->delete($assertingFile);
	}

	/**
	 * @test
	 */
	public function test_basic_resource_controller_make_command ()
	{
		$assertingFile = $this->controllerPath . '/BasicResourceController.php';
		$assertingAgainst = __DIR__ . '/Assertions/BasicResourceController.php.assertion';

		$this->artisan('make:controller', [
			'name' => 'BasicResourceController',
			'--resource' => TRUE,
			'--no-interaction' => TRUE
		]);

		$this->assertFileContents($assertingFile, $assertingAgainst);
		$this->fs->delete($assertingFile);
	}

	/**
	 * @test
	 */
	public function test_parent_resource_controller_make_command ()
	{
		$assertingFile = $this->controllerPath . '/ParentResourceController.php';
		$assertingAgainst = __DIR__ . '/Assertions/ParentResourceController.php.assertion';

		$this->artisan('make:controller', [
			'name' => 'ParentResourceController',
			'--resource' => TRUE,
			'--parent' => 'BasicResourceController',
			'--no-interaction' => TRUE
		]);

		$this->assertFileContents($assertingFile, $assertingAgainst);
		$this->fs->delete($assertingFile);
	}

	/**
	 * @test
	 */
	public function test_api_resource_controller_make_command ()
	{
		$assertingFile = $this->controllerPath . '/APIResourceController.php';
		$assertingAgainst = __DIR__ . '/Assertions/APIResourceController.php.assertion';

		$this->artisan('make:controller', [
			'name' => 'APIResourceController',
			'--resource' => TRUE,
			'--api' => TRUE,
			'--no-interaction' => TRUE
		]);

		$this->assertFileContents($assertingFile, $assertingAgainst);
		$this->fs->delete($assertingFile);
	}

	/**
	 * @test
	 */
	public function test_parent_api_resource_controller_make_command ()
	{
		$assertingFile = $this->controllerPath . '/ParentAPIResourceController.php';
		$assertingAgainst = __DIR__ . '/Assertions/ParentAPIResourceController.php.assertion';

		$this->artisan('make:controller', [
			'name' => 'ParentAPIResourceController',
			'--resource' => TRUE,
			'--api' => TRUE,
			'--parent' => 'BasicController',
			'--no-interaction' => TRUE
		]);

		$this->assertFileContents($assertingFile, $assertingAgainst);
		$this->fs->delete($assertingFile);
	}

}
