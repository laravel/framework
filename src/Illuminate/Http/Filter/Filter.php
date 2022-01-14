<?php

namespace Illuminate\Http\Filter;

use Closure;
use Illuminate\Http\Request;
use \Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Traits\Macroable;

abstract class Filter
{
    use Macroable;

    /**
     * request parameter, which should be filtered by this class.
     *
     * @var string
     */
    public $filterParameter;

    /**
     * @var Request
     */
    public $request;

    /**
     * @param  Request  $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * handle an incoming request.
     *
     * @param  $model
     * @param  Closure  $next
     * @return mixed
     */
    public function handle($model, Closure $next)
    {
        $builder = $next($model);
        $cleanRequest = array_filter($this->request->all());
        $value = $this->request->get($this->filterParameter);

        return array_key_exists($this->filterParameter, $cleanRequest) ?
            $this->apply($value, $builder) :
            $builder;
    }

    /**
     * @param mixed $value
     * @param $builder
     * @return Builder
     */
    abstract public function apply($value, $builder);
}
