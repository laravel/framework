<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Contracts\View\ViewCompilationException;

trait CompilesApplied
{
    /**
     * Compile the conditional applied statement into valid PHP.
     */
    protected function compileApplied(string|null $expression): string
    {
        preg_match('/^\(([\'"])([^\'"]+)\1,\1([^\'"]+)\1,\1([^\'"]+)\1\)$/', $expression ?? '', $matches);

        if (count($matches) === 0)
            throw new ViewCompilationException('Malformed @applied statement.');

        $expression = str_replace('(', '', $expression);
        $expression = str_replace(')', '', $expression);
        $result = explode(',', $expression);

        $filter  = $result[0];
        $value  = $result[1];
        $appliedCondition  = $result[2];


        return "<?php echo (request()->has({$filter}) and request()->get({$filter}) == {$value}) ? {$appliedCondition} : ''; ?>";
    }
}
