<?php

class FoundationDispatchesCommandsTraitTest extends PHPUnit_Framework_TestCase {

	public function testCommandsCanBeMarshalled()
	{
		$instance = new FoundationDispatchesCommandsTest;

		$command = $instance->testMarshalFromArray();
		$this->assertEquals('taylor', $command->firstName);
		$this->assertEquals('otwell', $command->lastName);

		$command = $instance->testMarshalFromRequest();
		$this->assertEquals('taylor', $command->firstName);
		$this->assertEquals('otwell', $command->lastName);
	}

}

class FoundationDispatchesCommandsTest {
	use Illuminate\Foundation\Bus\DispatchesCommands;
	public function testMarshalFromArray()
	{
		return $this->marshalFromArray('FoundationDispatchesCommandsTestCommand', ['firstName' => 'taylor']);
	}
	public function testMarshalFromRequest()
	{
		$request = Illuminate\Http\Request::create('/', 'GET', ['firstName' => 'taylor']);
		return $this->marshal('FoundationDispatchesCommandsTestCommand', $request);
	}
}

class FoundationDispatchesCommandsTestCommand {
	public $lastName;
	public $firstName;
	public function __construct($firstName, $lastName = 'otwell')
	{
		$this->lastName = $lastName;
		$this->firstName = $firstName;
	}
}
