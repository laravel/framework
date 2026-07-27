<?php

namespace Illuminate\Console;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class Parser
{
    /**
     * Parse the given console command definition into an array.
     *
     * @param  string  $expression
     * @return array{string, array{}, array{}}|array{string, \Symfony\Component\Console\Input\InputArgument[], \Symfony\Component\Console\Input\InputOption[]}
     *
     * @throws \InvalidArgumentException
     */
    public static function parse(string $expression)
    {
        $name = static::name($expression);

        if (preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches) && count($matches[1])) {
            return array_merge([$name], static::parameters($matches[1]));
        }

        return [$name, [], []];
    }

    /**
     * Extract the name of the command from the expression.
     *
     * @param  string  $expression
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    protected static function name(string $expression)
    {
        if (! preg_match('/[^\s]+/', $expression, $matches)) {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        return $matches[0];
    }

    /**
     * Extract all parameters from the tokens.
     *
     * @param  string[]  $tokens
     * @return array{\Symfony\Component\Console\Input\InputArgument[], \Symfony\Component\Console\Input\InputOption[]}
     */
    protected static function parameters(array $tokens)
    {
        $arguments = [];

        $options = [];

        foreach ($tokens as $token) {
            if (preg_match('/^-{2,}(.*)/', $token, $matches)) {
                $options[] = static::parseOption($matches[1]);
            } else {
                $arguments[] = static::parseArgument($token);
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
    protected static function parseArgument(string $token)
    {
        [$token, $description] = static::extractDescription($token);

        return match (true) {
            str_ends_with($token, '?*') => new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description),
            str_ends_with($token, '*') => new InputArgument(trim($token, '*'), InputArgument::IS_ARRAY | InputArgument::REQUIRED, $description),
            str_ends_with($token, '?') => new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description),
            (bool) preg_match('/(.+)\=\*(.+)/', $token, $matches) => new InputArgument($matches[1], InputArgument::IS_ARRAY, $description, preg_split('/,\s?/', $matches[2])),
            (bool) preg_match('/(.+)\=(.+)/', $token, $matches) => new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]),
            default => new InputArgument($token, InputArgument::REQUIRED, $description),
        };
    }

    /**
     * Parse an option expression.
     *
     * @param  string  $token
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected static function parseOption(string $token)
    {
        [$token, $description] = static::extractDescription($token);

        preg_match('/^(?:(.*?)\s*\|\s*)?(!)?([^=]+?)(?:(=)(\*)?(.*))?$/', $token, $matches);

        [$shortcut, $mode, $token, $array, $default] = [
            $matches[1] !== '' ? $matches[1] : null,
            $matches[2] === '!' ? InputOption::VALUE_REQUIRED : InputOption::VALUE_OPTIONAL,
            $matches[3],
            ($matches[5] ?? '') === '*',
            $matches[6] ?? '',
        ];

        if (($matches[4] ?? '') !== '=') {
            return new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
        }

        if ($array) {
            $mode |= InputOption::VALUE_IS_ARRAY;
        }

        return new InputOption($token, $shortcut, $mode, $description, match (true) {
            $default === '' => null,
            $array => preg_split('/,\s?/', $default),
            default => $default,
        });
    }

    /**
     * Parse the token into its token and description segments.
     *
     * @param  string  $token
     * @return array{string, string}
     */
    protected static function extractDescription(string $token)
    {
        $parts = preg_split('/\s+:\s+/', trim($token), 2);

        return count($parts) === 2 ? $parts : [$token, ''];
    }
}
