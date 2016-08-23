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
	}

}

interface iface
{
	function f1();
}

$container = new Illuminate\Container\Container();
