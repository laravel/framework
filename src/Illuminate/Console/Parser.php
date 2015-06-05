<?php

namespace Illuminate\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Parser
{
    /**
     * Parse the given console command definition into an array.
     *
     * @param  string  $expression
     * @return array
     */
    public static function parse($expression)
    {
        if (trim($expression) === '') {
            throw new InvalidArgumentException('Console command definition is empty.');
        }

        $tokens = array_values(array_filter(
            array_map('trim', explode('{', $expression))
        ));

        return [
            array_shift($tokens), static::arguments($tokens), static::options($tokens),
        ];
    }

    /**
     * Extract all of the arguments from the tokens.
     *
     * @param  array  $tokens
     * @return array
     */
    protected static function arguments(array $tokens)
    {
        return array_filter(array_map(function ($token) {
            if (starts_with($token, '{') && !starts_with($token, '{--')) {
                return static::parseArgument(trim($token, '{}'));
            }
        }, $tokens));
    }

    /**
     * Extract all of the options from the tokens.
     *
     * @param  array  $tokens
     * @return array
     */
    protected static function options(array $tokens)
    {
        return array_filter(array_map(function ($token) {
            if (starts_with($token, '{--')) {
                return static::parseOption(ltrim(trim($token, '{}'), '-'));
            }
        }, $tokens));
    }

    /**
     * Parse an argument expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function parseArgument($token)
    {
        list($name, $description) = static::parseToken($token);

        switch (true) {
            case ends_with($name, '?*'):
                return new InputArgument(trim($name, '?*'), InputArgument::IS_ARRAY, $description);

            case ends_with($name, '*'):
                return new InputArgument(trim($name, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description);

            case ends_with($name, '?'):
                return new InputArgument(trim($name, '?'), InputArgument::OPTIONAL, $description);

            case (preg_match('/(.+)\=(.+)/', $name, $matches)):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);

            default:
                return new InputArgument($name, InputArgument::REQUIRED, $description);
        }
    }

    /**
     * Parse an option expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function parseOption($token)
    {
        list($name, $shortcut, $description) = static::parseToken($token, 'option');

        switch (true) {
            case ends_with($name, '='):
                return new InputOption(trim($name, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);

            case ends_with($name, '=*'):
                return new InputOption(trim($name, '=*'), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);

            case (preg_match('/(.+)\=(.+)/', $name, $matches)):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, $matches[2]);

            default:
                return new InputOption($name, $shortcut, InputOption::VALUE_NONE, $description);
        }
    }

    /**
     * parse the token to extract description
     * and shortcut(if present)
     *
     *@param $for
     * @param $token
     *
     * @return array
     */
    protected static function parseToken($token, $for = '')
    {
        $haystack = explode(':', $token);

        $name = $haystack[0];
        $description = isset($haystack[1]) ? $haystack[1] : '';

        if(!strcmp($for, 'option')) {
            $haystack = explode(',', $name);
            $name = $haystack[0];
            $shortcut = isset($haystack[1]) ? $haystack[1] : null;

            return [$name, $shortcut, $description];
        }

        return [$name, $description];
    }
}
