<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

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


	public function testResgisterMacroAndCallWithoutStatic()
	{
		$macroTrait = $this->macroTrait;
		$macroTrait::macro(__CLASS__, function() { return 'Taylor'; });
		$this->assertEquals('Taylor', $macroTrait->{__CLASS__}());
	}

}
