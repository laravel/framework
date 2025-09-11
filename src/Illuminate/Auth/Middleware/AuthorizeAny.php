<?php

namespace Illuminate\Auth\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use function Illuminate\Support\enum_value;

class AuthorizeAny
{
    /**
     * The gate instance.
     *
     * @var \Illuminate\Contracts\Auth\Access\Gate
     */
    protected $gate;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Access\Gate  $gate
     */
    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Specify the abilities and models for the middleware.
     *
     * @param  array  $abilities
     * @param  string  ...$models
     * @return string
     */
    public static function using(array $abilities, ...$models)
    {
        $abilities = array_map(fn ($a) => enum_value($a), $abilities);

        return static::class.':'.implode('|', [...$abilities, ...$models]);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $abilities
     * @param  array  ...$models
     * @return mixed
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function handle($request, Closure $next, string $abilities, ...$models)
    {
        $abilities = explode('|', $abilities);
        $args = $this->getGateArguments($request, $models);

        foreach ($abilities as $ability) {
            if ($this->gate->check($ability, $args)) {
                return $next($request);
            }
        }

        throw new AuthorizationException;
    }

    /**
     * Resolve models for the gate arguments.
     */
    protected function getGateArguments($request, array $models): array
    {
        return (new Collection($models))
            ->map(fn ($model) => $model instanceof Model ? $model : $this->getModel($request, $model))
            ->all();
    }

    protected function getModel($request, string $model)
    {
        if (str_contains($model, '\\')) {
            return trim($model);
        }

        return $request->route($model, null)
            ?? ((preg_match("/^['\"](.*)['\"]$/", trim($model), $matches)) ? $matches[1] : null);
    }
}
