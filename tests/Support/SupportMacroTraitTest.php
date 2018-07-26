<?php


class SupportMacroTraitTest extends TestCase {

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

}
