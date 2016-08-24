<?php

require("vendor/autoload.php");

class Test
{

	public function __construct($first, Test2 $test2, $last)
	{
		dump($first);
		dump($last);
	}

}

class Test2
{

	public function __construct(Test3 $test3)
	{
	}

}

class Test3
{
	public function __construct()
	{
		dump("CONSTRUCT");
	}

	public static function test()
	{
	}

	public function test2()
	{
	}

}

$container = new Illuminate\Container\Container();

$container['foo'] = function () {
    return (object) ['name' => 'taylor'];
};
$container->extend('foo', function ($old, $container) {
    $old->age = 26;

    return $old;
});

$result = $container->make('foo');

dump($result);