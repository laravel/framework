<?php

namespace Illuminate\Database\Eloquent\Factories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BelongsToRelationship
{
    protected $factory;

    /**
     * The relationship name.
     *
     * @var string
     */
    protected $relationship;

    /**
     * The cached, resolved parent instance ID.
     *
     * @var mixed
     */
    protected $resolved;

    public function __construct(Factory $factory, $relationship)
    {
        $this->factory = $factory;
        $this->relationship = $relationship;
    }

    /**
     * Get the parent model attributes and resolvers for the given child model.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return array
     */
    public function attributesFor(Model $model)
    {
        $relationship = $model->{$this->relationship}();

        return $relationship instanceof MorphTo ? [
            $relationship->getMorphType() => (new $this->factory->model)->getMorphClass(),
            $relationship->getForeignKeyName() => $this->resolver(),
        ] : [
            $relationship->getForeignKeyName() => $this->resolver(),
        ];
    }

    /**
     * Get the deferred resolver for this relationship's parent ID.
     *
     * @return \Closure
     */
    protected function resolver()
    {
        return function () {
            if (! $this->resolved) {
                return $this->resolved = $this->factory->create()->getKey();
            }

            return $this->resolved;
        };
    }
}
