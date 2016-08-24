<?php

require("vendor/autoload.php");

class Test
{
	public function __construct(Test2 $test2)
	{
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

interface ITest
{
}

$container = new Illuminate\Container\Container();
// $container = new Illuminate\Container\ContainerOld();

for ($i = 0; $i < 10000; $i++) {
	$container->make(Test::class);
}

