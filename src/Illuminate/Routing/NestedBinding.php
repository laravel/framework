<?php

namespace Illuminate\Routing;

class NestedBinding
{
    /**
     * The relationship method name used in querying the relationship.
     *
     * @var string
     */
    protected $relation;

    /**
     * The instance that acts as the parent of the current nested parameter.
     *
     * @var object
     */
    protected $relatedInstance;

    /**
     * RelatedBinding constructor.
     *
     * @param  string  $relation
     * @param  $relatedInstance
     */
    public function __construct(string $relation, $relatedInstance)
    {
        $this->relation = $relation;
        $this->relatedInstance = $relatedInstance;
    }

    /**
     * Gets the relationship method name used in querying the relationship.
     *
     * @return string
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Gets the instance that acts as the parent of the current nested parameter.
     *
     * @return object
     */
    public function getRelatedInstance()
    {
        return $this->relatedInstance;
    }

    /**
     * Sets the relationship between the parent binded parameter and the current nested parameter for implicit binding.
     *
     * @param  Route  $route
     * @param  $parameters
     * @param  $pointer
     *
     * @return null|$this
     */
    public static function setRelationshipForImplicitBinding(Route $route, $parameters, $pointer)
    {
        $nestedBindings = $route->nestedBindings();

        // If the pointer is equal to zero, therefore it points to the first parameter, which always will be independent
        // and cannot be nested from any parameter, therefore we skip the operation.
        if (empty($nestedBindings) || $pointer === 0) {
            return null;
        }

        // The pointer is shifted backwards by one step, because the $parameters array will always be ahead of
        // the $nestedBindings array by one which is the independent parameter which is the first element in the $parameters array.
        $pointer--;

        return (new static($nestedBindings[$pointer], $route->parameter($parameters[$pointer]->name)));
    }
}
