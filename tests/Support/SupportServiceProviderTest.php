<?php

class SupportServiceProviderTest extends TestCase {

	public static function setUpBeforeClass()
	{
		require_once __DIR__.'/stubs/providers/SuperProvider.php';
		require_once __DIR__.'/stubs/providers/SuperSuperProvider.php';
	}


	public function testPackageNameCanBeGuessed()
	{
		$superProvider = new SuperProvider(null);
		$this->assertEquals(realpath(__DIR__.'/'), $superProvider->guessPackagePath());

		$superSuperProvider = new SuperSuperProvider(null);
		$this->assertEquals(realpath(__DIR__.'/'), $superSuperProvider->guessPackagePath());
	}

}
