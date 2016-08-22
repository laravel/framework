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

$container->bind(['bam' => 'boom'], function () {
    return 'pow';
});

dump($container->make('bam'));
