<?php

namespace Illuminate\Support\Facades;

/**
 * @method static array getClassComponentAliases()
 * @method static array getCustomDirectives()
 * @method static array getExtensions()
 * @method static bool check(string $name, array ...$parameters)
 * @method static string compileString(string $value)
 * @method static string getPath()
 * @method static string stripParentheses(string $expression)
 * @method static void aliasComponent(string $path, string|null $alias = null)
 * @method static void aliasInclude(string $path, string|null $alias = null)
 * @method static void compile(string|null $path = null)
 * @method static void component(string $class, string|null $alias = null, string $prefix = '')
 * @method static void components(array $components, string $prefix = '')
 * @method static void directive(string $name, callable $handler)
 * @method static void extend(callable $compiler)
 * @method static void if(string $name, callable $callback)
 * @method static void include(string $path, string|null $alias = null)
 * @method static void precompiler(callable $precompiler)
 * @method static void setEchoFormat(string $format)
 * @method static void setPath(string $path)
 * @method static void withDoubleEncoding()
 * @method static void withoutComponentTags()
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
        return 'blade.compiler';
    }
}
