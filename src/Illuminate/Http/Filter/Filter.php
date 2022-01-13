<?php

namespace Illuminate\Http\Filter;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;

abstract class Filter
{
    use Macroable;

    /**
     * request parameter, which should be filtered by this class.
     *
     * @var string
     */
    public $filterParameters;

    /**
     * handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $builder = $next($request);
        $cleanRequest = array_filter($request->all());
        return array_key_exists($this->filterParameters, $cleanRequest) ? $this->apply($builder) : $builder;
    }

    abstract public function apply($builder);
}
