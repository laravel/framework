<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpFoundation\ParameterBag;

class TransformsRequest
{
    /**
     * The maximum cumulative size of the keys processed per request, in bytes.
     *
     * @var int
     */
    protected $maxKeyBytes = 20 * 1024 * 1024;

    /**
     * The cumulative size of the keys processed for the current request.
     *
     * @var int
     */
    protected $keyBytes = 0;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->keyBytes = 0;

        $this->clean($request);

        return $next($request);
    }

    /**
     * Clean the request's data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function clean($request)
    {
        $this->cleanParameterBag($request->query);

        if ($request->isJson()) {
            $this->cleanParameterBag($request->json());
        } elseif ($request->request !== $request->query) {
            $this->cleanParameterBag($request->request);
        }
    }

    /**
     * Clean the data in the parameter bag.
     *
     * @param  \Symfony\Component\HttpFoundation\ParameterBag  $bag
     * @return void
     */
    protected function cleanParameterBag(ParameterBag $bag)
    {
        $bag->replace($this->cleanArray($bag->all()));
    }

    /**
     * Clean the data in the given array.
     *
     * @param  array  $data
     * @param  string  $keyPrefix
     * @return array
     */
    protected function cleanArray(array $data, $keyPrefix = '')
    {
        $prefixLength = strlen($keyPrefix);

        foreach ($data as $key => $value) {
            $this->keyBytes += $prefixLength + strlen((string) $key);

            if ($this->keyBytes > $this->maxKeyBytes) {
                throw new PostTooLargeException('The cumulative size of the input keys is too large.');
            }

            $data[$key] = $this->cleanValue($keyPrefix.$key, $value);
        }

        return $data;
    }

    /**
     * Clean the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function cleanValue($key, $value)
    {
        if (is_array($value)) {
            return $this->cleanArray($value, $key.'.');
        }

        return $this->transform($key, $value);
    }

    /**
     * Transform the given value.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return mixed
     */
    protected function transform($key, $value)
    {
        return $value;
    }
}
