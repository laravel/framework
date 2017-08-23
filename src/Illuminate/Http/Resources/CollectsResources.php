<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

trait CollectsResources
{
    /**
     * Map the given collection resource into its individual resources.
     *
     * @param  mixed  $resource
     * @return mixed
     */
    protected function collectResource($resource)
    {
        $this->collection = $resource->mapInto($this->collects());

        if ($this->containsModels()) {
            $this->collection = new EloquentCollection($this->collection->all());
        }

        return $resource instanceof AbstractPaginator
                    ? $resource->setCollection($this->collection)
                    : $this->collection;
    }

    /**
     * Determine if the collection contains models.
     *
     * @return bool
     */
    protected function containsModels()
    {
        return $this->collection->contains(function ($item) {
            return $item->resource instanceof Model;
        });
    }

    /**
     * Get the resource that this resource collects.
     *
     * @return string
     */
    protected function collects()
    {
        if ($this->collects) {
            return $this->collects;
        }

        if (Str::endsWith(class_basename($this), 'Collection') &&
            class_exists($class = Str::replaceLast('Collection', '', get_class($this)))) {
            return $class;
        }

        throw new UnknownCollectionException($this);
    }

    /**
     * Get an iterator for the resource collection.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return $this->collection->getIterator();
    }
}
