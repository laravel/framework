<?php

namespace Illuminate\View\Compilers\Concerns;

use Exception;
use Illuminate\Contracts\View\ViewCompilationException;
use PhpToken;

trait CompilesVar
{
    /**
     * Compiles variable statement into valid php
     *
     * @param string $expression
     * @return string
     */
    protected function compileVar($expression)
    {
        $expression ='<?php '.substr($expression, 1, -1).' ?>';
        if ($this->lint($expression)) {
            return $expression;
        }

        throw new ViewCompilationException('Statement @var expects variable definition');
    }

    private function lint($expression)
    {
        $tokens = PhpToken::tokenize($expression);

        static $signature = [
            T_VARIABLE,
            ['=', '+=', '-=', '*=', '/=', '%=', '**=', '&=', '|=', '^=', '<<=', '>>=', '.=', '++', '--'],
        ];

        $curent = 0;

        foreach ($tokens as $index => $token) {
            if (!isset($signature[$curent])) {
                // Variable definition tokens are present, if there are no new
                // its not valid so we return false
                return
                    $index < count($tokens) - 2
                    || in_array($tokens[$index - 1], ['++', '--']);
            }

            // Ignoring first tokens - `<?php` and whitespaces
            if ($index < 1
                || $token->is(T_WHITESPACE)
            ) {
                continue;
            }

            if (!$token->is($signature[$curent])) {
                return false;
            }

            $curent++;
        }

        return false;
    }
}
