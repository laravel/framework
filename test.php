<?php

/*

Breacking changes :

-Container::call cant't anymore take a default method (3rd param)
-Container::call doesn't resolve from container

Features :

-Container::call and Container::make have the same behaviour except that Container::call
resolve from outside the container and Container::make resolve from inside the container
-Container::call and Container::make supprots : closure, "class@method",
[object, "method"], "object::method" and "class" notations

 */

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

	public function test() {}
}

interface ITest
{
}

$container = new Illuminate\Container\Container();
// $container = new Illuminate\Container\ContainerOld();

//Plain, Service, Singleton

/*
$container->singleton(Test::class);

for ($i = 0; $i < 10000; $i++) {
	$container->make(Test::class);
}
*/

/*
class Is
{
	public static function isClass($subject)
	{
	    return is_string($subject) && class_exists($subject);
	}

	public static function isMethod($subject)
	{
        return is_callable($subject) && !self::isFunction($subject);
	}

	public static function isFunction($subject)
	{
        return is_callable($subject) && ($subject instanceof Closure || is_string($subject) && function_exists($subject));
	}
}

$tests = [
	"closure" => function() {},
	"function" => "strlen",
	"class" => "Test3",
	"static_method" => "Is::isClass",
	"array_method" => [new Test3, "test"]
];

foreach ($tests as $key => $value) {
	dump($key . " -> " . (Is::isClass($value) ? "true" : "false"));
}
*/
