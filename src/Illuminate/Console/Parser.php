<?php

namespace Illuminate\Console;

use Illuminate\Support\Str;
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

        preg_match('/[^\s]+/', $expression, $matches);

        if (isset($matches[0])) {
            $name = trim($matches[0]);
        } else {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        preg_match_all('/\{.*?\}/', $expression, $matches);

        $tokens = isset($matches[0]) ? $matches[0] : [];

        return [
            $name, static::arguments($tokens), static::options($tokens),
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
        return array_values(array_filter(array_map(function ($token) {
            if (Str::startsWith($token, '{') && !Str::startsWith($token, '{--')) {
                return static::parseArgument(trim($token, '{}'));
            }
        }, $tokens)));
    }

    /**
     * Extract all of the options from the tokens.
     *
     * @param  array  $tokens
     * @return array
     */
    protected static function options(array $tokens)
    {
        return array_values(array_filter(array_map(function ($token) {
            if (Str::startsWith($token, '{--')) {
                return static::parseOption(ltrim(trim($token, '{}'), '-'));
            }
        }, $tokens)));
    }

    /**
     * Parse an argument expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    protected static function parseArgument($token)
    {
        $description = null;

        if (Str::contains($token, ' : ')) {
            list($token, $description) = explode(' : ', $token, 2);

            $token = trim($token);

            $description = trim($description);
        }

        switch (true) {
            case Str::endsWith($token, '?*'):
                return new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description);

            case Str::endsWith($token, '*'):
                return new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description);

            case Str::endsWith($token, '?'):
                return new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description);

            case (preg_match('/(.+)\=(.+)/', $token, $matches)):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);

            default:
                return new InputArgument($token, InputArgument::REQUIRED, $description);
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
        $description = null;

        if (Str::contains($token, ' : ')) {
            list($token, $description) = explode(' : ', $token);

            $token = trim($token);

            $description = trim($description);
        }

        switch (true) {
            case Str::endsWith($token, '='):
                return new InputOption(trim($token, '='), null, InputOption::VALUE_OPTIONAL, $description);

            case Str::endsWith($token, '=*'):
                return new InputOption(trim($token, '=*'), null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);

            case (preg_match('/(.+)\=(.+)/', $token, $matches)):
                return new InputOption($matches[1], null, InputOption::VALUE_OPTIONAL, $description, $matches[2]);

            default:
                return new InputOption($token, null, InputOption::VALUE_NONE, $description);
        }
    }
}
