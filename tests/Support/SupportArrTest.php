<?php

use Illuminate\Support\Arr;

class SupportArrTest extends PHPUnit_Framework_TestCase {

	public function testAssociativeFromDotKeys()
	{
		$testArray = array(
			'levelOne' => 1,
			'levelTwo.someKey' => 2,
			'levelTwo.anotherKey' => 3,
			'levelThree.moreKey.withMoreDepth' => 4,
			'levelThree.moreKey.andAssigningToSameDepthKey' => 5,
		);

		$resultArray = Arr::associativeFromDotKeys($testArray);

		$this->assertEquals(array(
			'levelOne' => 1,
			'levelTwo' => array(
				'someKey' => 2,
				'anotherKey' => 3,
			),
			'levelThree' => array(
				'moreKey' => array(
					'withMoreDepth' => 4,
					'andAssigningToSameDepthKey' => 5,
				)
			)
		), $resultArray);
	}

}
