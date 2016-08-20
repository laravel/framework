<?php

require("src/Illuminate/Container/Resolver.php");
require("src/Illuminate/Container/Container.php");

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

$class = $container->resolve(Test::class);

var_dump($class);
