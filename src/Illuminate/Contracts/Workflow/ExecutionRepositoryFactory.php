<?php

namespace Illuminate\Contracts\Workflow;

interface ExecutionRepositoryFactory
{
    /**
     * Get an execution repository instance by name.
     *
     * @param  \UnitEnum|string|null  $name
     * @return \Illuminate\Contracts\Workflow\ExecutionRepository
     */
    public function store($name = null);
}

/*
 * TODOs:
 * 1. Test serialization
 * 2. Add the ability to choose whether to keep the durable execution in cache after job is complete
 * 3. How to handle failures?
 * 4. Flesh out this factory pattern. I think I want it to implement the interface, but we have to add all the methods to it (think like LogManager). The easier option is the MultipleInstanceManager (like ConcurrencyManager)
 */
