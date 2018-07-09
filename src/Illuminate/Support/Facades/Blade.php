<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void compile($path = null)
 * @method static string getPath()
 * @method static void setPath($path)
 * @method static string compileString($value)
 * @method static string stripParentheses($expression)
 * @method static void extend(callable $compiler)
 * @method static array getExtensions()
 * @method static void if($name, callable $callback)
 * @method static bool check($name, ...$parameters)
 * @method static void component($path, $alias = null)
 * @method static void include($path, $alias = null)
 * @method static void directive($name, callable $handler)
 * @method static array getCustomDirectives()
 * @method static void setEchoFormat($format)
 * @method static void withDoubleEncoding()
 * @method static void withoutDoubleEncoding()
 *
 * @see \Illuminate\View\Compilers\BladeCompiler
 */
class Blade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return static::$app['view']->getEngineResolver()->resolve('blade')->getCompiler();
    }
}
