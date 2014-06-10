<?php

use Illuminate\Workbench\Console\WorkbenchMakeCommand;
use \Illuminate\Filesystem\Filesystem;
use Mockery as m;

class WorkbenchMakeCommandTest extends PHPUnit_Framework_TestCase {

	protected function tearDown()
	{
		m::close();
	}

	/**
	 * @expectedException PHPUnit_Framework_Error_Warning
	 * @expectedExceptionCode 2
	 */
	public function testCreatePackageSuccess()
	{
		$creator = $this->getMock('\Illuminate\Workbench\PackageCreator', [], [new Filesystem()]);
		$creator->expects($this->once())->method('create')->will($this->returnValue('foo'));
		$this->generateAppStub($creator)->call('workbench', ['package' => 'vendor/package']);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Package name must respect the vendor/name format. Supplied name was "package"
	 */
	public function testCreateWorkbenchWithInvalidPackageName()
	{
		$creator = $this->getMock('\Illuminate\Workbench\PackageCreator', [], [new Filesystem()]);
		$creator->expects($this->never())->method('create');
		$this->generateAppStub($creator)->call('workbench', ['package' => 'package']);
	}

	private function generateAppStub($creator)
	{
		$app = new \Illuminate\Console\Application();
		$command = m::mock(new WorkbenchMakeCommand($creator));
		$app->add($command);
		return $app;
	}
	
}
