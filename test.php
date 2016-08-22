<?php

require("vendor/autoload.php");

class Test
{

	public function __construct()
	{
	}

	public function work()
	{
		return func_get_args();
	}

}

interface TestInterface
{
}

class Test2
{

	public function __construct()
	{
	}

	public function Antoine(Test $test)
	{
		return "OK";
	}

}

$container = new Illuminate\Container\Container();

$container->resolving('stdClass', function ($object) {
    return $object->name = 'taylor';
});
$container->bind('foo', function () {
    return new StdClass;
});
$instance = $container->make('foo');

dump($instance);
