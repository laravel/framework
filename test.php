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

// $container = new Illuminate\Container\Container();

/*

After resolving xxx, call callback

-If resolved is instanceof xxx
-If container bind is equal to xxx

If event is a class -> will check instance for all

 */

