<?php

namespace Illuminate\Database\Eloquent\Factory;

use Closure;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;

class RelationRequest
{
    use PrototypesModels;

    /**
     * The parent model requesting relations.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $class;

    /**
     * The batch number.
     *
     * @var int
     */
    protected $batch;

    /**
     * The StateManager that holds all definitions.
     *
     * @var \Illuminate\Database\Eloquent\Factory\StateManager
     */
    protected $stateManager;

    /**
     * A cached name of the related model class.
     *
     * @var string|null
     */
    protected $cachedRelatedClass;

    /**
     * The (possibly nested) relations path.
     *
     * @var string
     */
    public $path;

    /**
     * The build function.
     *
     * @var callable|null
     */
    public $builder = null;

    /**
     * Create a new relationship request.
     *
     * @param  string  $class
     * @param  int  $batch
     * @param  \Illuminate\Database\Eloquent\Factory\StateManager  $stateManager
     * @param  mixed  $args
     * @return void
     */
    public function __construct($class, $batch, StateManager $stateManager, $args)
    {
        [$this->class, $this->batch, $this->stateManager] = [$class, $batch, $stateManager];

        collect($args)
            ->pipe(Closure::fromCallable([$this, 'findAndPopRelationName']))
            ->tap(Closure::fromCallable([$this, 'failOnMissingRelation']))
            ->each(Closure::fromCallable([$this, 'parseArgument']));
    }

    /**
     * Create a new relationship request for nested relations.
     *
     * @return static
     */
    public function createNestedRequest()
    {
        $request = new static($this->getRelatedClass(), $this->batch, $this->stateManager, $this->getNestedPath());
        $request->amount = $this->amount;
        $request->attributes = $this->attributes;
        $request->builder = $this->builder;
        $request->states = $this->states;

        return $request;
    }

    /**
     * Get the current batch no.
     *
     * @return int
     */
    public function getBatch()
    {
        return $this->batch;
    }

    /**
     * Get the nested path beyond immediate relation.
     *
     * @param  string|null  $path
     * @return string
     */
    public function getNestedPath($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        array_shift($nested);

        return implode('.', $nested);
    }

    /**
     * Get the class name of the related eloquent model.
     *
     * @return string
     */
    public function getRelatedClass()
    {
        $relation = $this->getRelationName();

        return $this->cachedRelatedClass = $this->cachedRelatedClass
            ?: get_class($this->model()->$relation()->getRelated());
    }

    /**
     * Get the name of the immediate relation.
     *
     * @param  string|null  $path
     * @return mixed
     */
    public function getRelationName($path = null)
    {
        $nested = explode('.', $path ?: $this->path);

        return array_shift($nested);
    }

    /**
     * Check if has nesting.
     *
     * @return bool
     */
    public function hasNesting()
    {
        return strpos($this->path, '.') !== false;
    }

    /**
     * Loop through arguments to detect a relation name.
     *
     * @param  \Illuminate\Support\Collection  $args
     * @return \Illuminate\Support\Collection
     */
    protected function findAndPopRelationName(Collection $args)
    {
        return $args->reject(function ($arg) {
            if ($match = (is_string($arg) && $this->isValidRelation($arg))) {
                $this->path = $arg;
            }

            return $match;
        });
    }

    /**
     * Check if a string represents a valid relation path.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isValidRelation($path)
    {
        $model = $this->model();
        $relation = $this->getRelationName($path);

        return method_exists($model, $relation) && $model->$relation() instanceof Relation;
    }

    /**
     * Get an instance of the model class.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function model()
    {
        return new $this->class;
    }

    /**
     * Parse each individual argument given to 'with'.
     *
     * @param  mixed  $arg
     * @return void
     */
    protected function parseArgument($arg)
    {
        if (is_null($arg)) {
            return;
        }

        if (is_numeric($arg)) {
            $this->amount = $arg;

            return;
        }

        if (is_array($arg) && ! isset($arg[0])) {
            $this->attributes = $arg;

            return;
        }

        if (is_callable($arg) && ! is_string($arg)) {
            $this->builder = $arg;

            return;
        }

        if (is_string($arg) && $this->isValidRelation($arg)) {
            $this->path = $arg;

            return;
        }

        if (is_string($arg) && $this->stateManager->definitionExists($this->getRelatedClass(), $arg)) {
            $this->definition = $arg;

            return;
        }

        if ($this->stateManager->presetsExists($this->getRelatedClass(), $arg)) {
            $this->presets = array_merge($this->presets, Arr::wrap($arg));

            return;
        }

        // If nothing else, we'll assume $arg represent some state.
        return $this->states = array_merge($this->states, Arr::wrap($arg));
    }

    /**
     * Fail build with a readable exception message.
     *
     * @param  \Illuminate\Support\Collection  $args
     */
    protected function failOnMissingRelation(Collection $args)
    {
        if (! $this->path) {
            throw new BadMethodCallException(
                'No matching relations could be found on model ['.$this->class.']. '.
                'Following possible relation names was checked: '.
                (
                ($testedRelations = $this->getPossiblyIntendedRelationships($args))->isEmpty()
                    ? '[NO POSSIBLE RELATION NAMES FOUND]'
                    : '['.$testedRelations->implode(', ').']'
                )
            );
        }
    }

    /**
     * Give the developer a readable list of possibly args
     * that they might have intended could be a relation,
     * but was invalid. Helpful for debugging purposes.
     *
     * @param  \Illuminate\Support\Collection  $args
     * @return \Illuminate\Support\Collection
     */
    protected function getPossiblyIntendedRelationships(Collection $args)
    {
        return $args
            ->filter(function ($arg) {
                return is_string($arg) || is_null($arg);
            })
            ->map(function ($arg) {
                return is_null($arg) ? 'NULL' : "'".$arg."'";
            });
    }
}
