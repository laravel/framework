<?php

use Mockery as m;

class FoundationArtisanTest extends TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testArtisanIsCalledWithProperArguments()
	{
		$artisan = $this->getMock('Illuminate\Foundation\Artisan', array('getArtisan'), array($app = new Illuminate\Foundation\Application));
		$artisan->expects($this->once())->method('getArtisan')->will($this->returnValue($console = m::mock('Illuminate\Console\Application[find]')));
		$console->shouldReceive('find')->once()->with('foo')->andReturn($command = m::mock('StdClass'));
		$command->shouldReceive('run')->once()->with(m::type('Symfony\Component\Console\Input\ArrayInput'), m::type('Symfony\Component\Console\Output\NullOutput'))->andReturnUsing(function($input, $output)
		{
			return $input;
		});

		$input = $artisan->call('foo', array('--bar' => 'baz'));
		$this->assertEquals('baz', $input->getParameterOption('--bar'));
	}

}
