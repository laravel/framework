<?php

namespace Illuminate\Foundation\VarDumper\Concerns;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\VarDumper\Casters\BuilderCaster;
use Illuminate\Foundation\VarDumper\Casters\CarbonCaster;
use Illuminate\Foundation\VarDumper\Casters\ContainerCaster;
use Illuminate\Foundation\VarDumper\Casters\DatabaseConnectionCaster;
use Illuminate\Foundation\VarDumper\Casters\HeaderBagCaster;
use Illuminate\Foundation\VarDumper\Casters\ModelCaster;
use Illuminate\Foundation\VarDumper\Casters\ParameterBagCaster;
use Illuminate\Foundation\VarDumper\Casters\RequestCaster;
use Illuminate\Foundation\VarDumper\Casters\ResponseCaster;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\VarDumper;

trait HandlesDumps
{
    /**
     * The variable cloner instance.
     *
     * @var VarCloner
     */
    protected $cloner = null;

    /**
     * Register as the default dumper.
     *
     * @return void
     */
    public function register()
    {
        VarDumper::setHandler(fn ($value) => $this->handle($value));
    }

    /**
     * Set the cloner instance.
     *
     * @param  VarCloner  $cloner
     * @return $this
     */
    public function setCloner($cloner)
    {
        $this->cloner = $cloner;

        return $this;
    }

    /**
     * Get the default cloner with all casters registered.
     *
     * @return VarCloner
     */
    public function getDefaultCloner()
    {
        $builderCaster = new BuilderCaster();

        return tap(new VarCloner())->addCasters([
            Closure::class   => [ReflectionCaster::class, 'unsetClosureFileInfo'],
            Container::class => new ContainerCaster(),
            ConnectionInterface::class => new DatabaseConnectionCaster(),
            BaseBuilder::class => $builderCaster,
            EloquentBuilder::class => $builderCaster,
            Relation::class => $builderCaster,
            CarbonInterface::class => new CarbonCaster(),
            HeaderBag::class => new HeaderBagCaster(),
            Model::class => new ModelCaster(),
            ParameterBag::class => new ParameterBagCaster(),
            Request::class => new RequestCaster(),
            Response::class => new ResponseCaster(),
        ]);
    }

    /**
     * Clone and dump a variable.
     *
     * @param  mixed  $value
     * @return void
     */
    public function handle($value)
    {
        $this->cloner ??= $this->getDefaultCloner();

        $this->dumpWithSource($this->cloner->cloneVar($value));
    }
}
