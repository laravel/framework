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
            $name = $matches[0];
        } else {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        preg_match_all('/(\{([^\{\}]*((?1)\}|[^\}])*))\}/', $expression, $matches);

        $tokens = isset($matches[2]) ? $matches[2] : [];

        if (count($tokens)) {
            return array_merge([$name], static::parameters($tokens));
        }

        return [$name, [], []];
    }

    /**
     * Extract all of the parameters from the tokens.
     *
     * @param  array  $tokens
     * @return array
     */
    protected static function parameters(array $tokens)
    {
        $arguments = [];

        $options = [];

        foreach ($tokens as $token) {
            if (! Str::startsWith($token, '--')) {
                $arguments[] = static::parseArgument($token);
            } else {
                $options[] = static::parseOption(ltrim($token, '-'));
            }
        }

        return [$arguments, $options];
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

            case preg_match('/(.+?)\=(.+)/', $token, $matches):
                return new InputArgument($matches[1], InputArgument::OPTIONAL, $description, self::parseDefault($matches[2]));

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

        $shortcut = null;

        $matches = preg_split('/\s*\|\s*/', $token, 2);

        if (isset($matches[1])) {
            $shortcut = $matches[0];
            $token = $matches[1];
        }

        switch (true) {
            case Str::endsWith($token, '='):
                return new InputOption(trim($token, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);

            case Str::endsWith($token, '=*'):
                return new InputOption(trim($token, '=*'), $shortcut, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, $description);

            case preg_match('/(.+?)\=(.+)/', $token, $matches):
                return new InputOption($matches[1], $shortcut, InputOption::VALUE_OPTIONAL, $description, self::parseDefault($matches[2]));

            default:
                return new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
        }
    }

    /**
     * Parse a default value expression.
     *
     * @param  string  $default
     * @return mixed
     */
    protected static function parseDefault($default)
    {
        switch (true) {
            case preg_match('/^null$/i', $default):
                return null;
            case preg_match('/^(true|false)$/', $default):
                return $default === 'true';
            case preg_match('/^[1-9]\d*$/', $default):
                return (int) $default;
                break;
            case preg_match('/^(0?|[1-9]\d*)\.\d+$/', $default):
                return (float) $default;
            case preg_match('/^(?:(array\()|(\[)).*(?(1)\))(?(2)\])$/', $default):
                $default = preg_replace('/^array\((.*)\)$/', '[$1]', $default);

                if ($default === '[]') {
                    return [];
                }

                $default = preg_replace('/(?:(\d+)|(\'|")(.*?)\2)(\s*=>)/', '"$1$3":', $default, -1, $count);

                if ($count) {
                    $default = preg_replace(['/^\[/', '/\]$/'], ['{', '}'], $default);
                }

                return json_decode($default, true);
        }

        $default_json_decoded = json_decode($default);

        if (is_object($default_json_decoded)) {
            return $default_json_decoded;
        }

        return $default;
    }
}
