<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void compile(string $path) Compile the view at the given path.
 * @method static string getPath() Get the path currently being compiled.
 * @method static void setPath(string $path) Set the path currently being compiled.
 * @method static string compileString(string $value) Compile the given Blade template contents.
 * @method static string stripParentheses(string $expression) Strip the parentheses from the given expression.
 * @method static void extend(callable $compiler) Register a custom Blade compiler.
 * @method static array getExtensions() Get the extensions used by the compiler.
 * @method static void if (string $name, callable $callback) Register an "if" statement directive.
 * @method static bool check(string $name, array $parameters) Check the result of a condition.
 * @method static void directive(string $name, callable $handler) Register a handler for custom directives.
 * @method static array getCustomDirectives() Get the list of custom directives.
 * @method static void setEchoFormat(string $format) Set the echo format to be used by the compiler.
 * @method static string getCompiledPath(string $path) Get the path to the compiled version of a view.
 * @method static bool isExpired(string $path) Determine if the view at the given path is expired.
 * @method static string compileEchoDefaults(string $value) Compile the default values for the echo statement.
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
