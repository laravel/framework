<?php

namespace Illuminate\Bus\ExecutionContext;

use Illuminate\Contracts\Workflow\ExecutionRepository as ExecutionRepositoryContract;

class ExecutionRepository implements ExecutionRepositoryContract
{
    public function find(string $id)
    {
        // TODO: Implement find() method.
    }

    public function create(mixed $id)
    {
        // TODO: Implement create() method.
    }

    public function saveStep($state, string $name): void
    {
        // TODO: Implement saveStep() method.
    }

    public function delete($id): void
    {
        // TODO: Implement delete() method.
    }
}
