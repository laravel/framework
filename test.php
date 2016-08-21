<?php

require("vendor/autoload.php");

class Test
{

	public function __construct(Test2 $test2, $lol = null)
	{
	}

}

class Test2
{

	public function __construct()
	{
	}

	public function Antoine(Test $test)
	{
	}

}

$container = new Illuminate\Container\Container();

$container->bindService("Test", Test::class);

var_dump($container->resolve("Test"));
