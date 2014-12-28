<?php


class SupportMacroTraitTest extends \PHPUnit_Framework_TestCase {

	private $macroTrait;

	public function setUp()
	{
		$this->macroTrait = $this->createObjectForTrait();
	}

	private function createObjectForTrait()
	{
		$traitName = 'Illuminate\Support\Traits\MacroableTrait';
		return $this->getObjectForTrait($traitName);
	}


	public function testRegisterMacro()
	{
		$macroTrait = $this->macroTrait;
		$macroTrait::macro(__CLASS__, function() { return 'Taylor'; });
		$this->assertEquals('Taylor', $macroTrait::{__CLASS__}());
	}


	public function testRegisterMacroAndCallWithoutStatic()
	{
		$macroTrait = $this->macroTrait;
		$macroTrait::macro(__CLASS__, function() { return 'Taylor'; });
		$this->assertEquals('Taylor', $macroTrait->{__CLASS__}());
	}


	public function testWhenCallingMacroClosureIsBoundToObject()
	{
		TestMacroTrait::macro('tryInstance', function() { return $this->protectedVariable; } );
		TestMacroTrait::macro('tryStatic', function() { return static::getProtectedStatic(); } );
		$instance = new TestMacroTrait;

		$result = $instance->tryInstance();
		$this->assertEquals('instance', $result);

		$result = TestMacroTrait::tryStatic();
		$this->assertEquals('static', $result);
	}

}

class TestMacroTrait {
	use Illuminate\Support\Traits\MacroableTrait;
	protected $protectedVariable = 'instance';
	protected static function getProtectedStatic()
	{
		return 'static';
	}
}
