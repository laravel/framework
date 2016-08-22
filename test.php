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

	public function __construct(Test3 $test2)
	{
	}

}

class Test3
{

	public function __construct()
	{
	}

}

$container = new Illuminate\Container\Container();

$container->extend('foo', function ($old, $container) {
    return $old.'bar';
});
$container['foo'] = 'foo';

dump($container->make('foo'));
