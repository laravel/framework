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
            array_map('trim', explode(' ', $expression))
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
        switch (true) {
            case ends_with($token, '?*'):
                return new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY);

            case ends_with($token, '*'):
                return new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED);

            case ends_with($token, '?'):
                return new InputArgument(trim($token, '?'), InputArgument::OPTIONAL);

            case (preg_match('/(.+)\=(.+)/', $token, $matches)):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, '', $matches[2]);

            default:
                return new InputArgument($token, InputArgument::REQUIRED);
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
        switch (true) {
            case ends_with($token, '='):
                return new InputOption(trim($token, '='), null, InputOption::VALUE_OPTIONAL);

            case ends_with($token, '=*'):
                return new InputOption(trim($token, '=*'), null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY);

            case (preg_match('/(.+)\=(.+)/', $token, $matches)):
                return new InputOption($matches[1], null, InputOption::VALUE_OPTIONAL, '', $matches[2]);

            default:
                return new InputOption($token, null, InputOption::VALUE_NONE);
        }
    }
}
