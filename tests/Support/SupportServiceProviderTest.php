<?php


class SupportServiceProviderTest extends PHPUnit_Framework_TestCase
{
	private $app;


	public function setUp()
	{
		parent::setUp();

		$this->app = $this->getMockBuilder('\Illuminate\Contracts\Foundation\Application')->getMock();
	}


	public function testMergeValuesNew()
	{
		$left = ['key' => 'value'];
		$right = ['new' => 'some value'];

		$merged = (new SupportServiceProviderForTest($this->app))->mergeValuesPublic($left, $right);

		$this->assertEquals(['key' => 'value', 'new' => 'some value'], $merged);
	}


	public function testMergeValuesOverwriteScalar()
	{
		$left = ['key' => ['value']];
		$right = ['key' => 'some value'];

		$merged = (new SupportServiceProviderForTest($this->app))->mergeValuesPublic($left, $right);

		$this->assertEquals(['key' => 'some value'], $merged);
	}


	public function testMergeValuesOverwriteArray()
	{
		$left = ['key' => ['sub' => 'value', 'some' => 'value']];
		$right = ['key' => ['some' => 'val']];

		$merged = (new SupportServiceProviderForTest($this->app))->mergeValuesPublic($left, $right);

		$this->assertEquals(['key' => ['sub' => 'value', 'some' => 'val']], $merged);
	}


	public function testMergeValuesOverwriteArrayRecursive()
	{
		$left = ['key' => ['sub' => ['some' => 'value']]];
		$right = ['key' => ['sub' => ['some' => 'val']]];

		$merged = (new SupportServiceProviderForTest($this->app))->mergeValuesPublic($left, $right);

		$this->assertEquals(['key' => ['sub' => ['some' => 'val']]], $merged);
	}
}


class SupportServiceProviderForTest extends \Illuminate\Support\ServiceProvider
{
	public function register() {}


	public function mergeValuesPublic(array $left, array $right)
	{
		return $this->mergeValues($left, $right);
	}
}