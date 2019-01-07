<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Pagination\AbstractPaginator;

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
        if ($resource instanceof MissingValue) {
            return $resource;
        }

        $collects = $this->collects();

        if($resource instanceof AbstractPaginator) {

            $this->collection = $collects && ! $resource->getCollection()->first() instanceof $collects
                ? $resource->getCollection()->mapInto($collects)
                : $resource->getCollection()->toBase();

            return $resource->setCollection($this->collection);
        } else {
            $this->collection = $collects && ! $resource->first() instanceof $collects
                ? $resource->mapInto($collects)
                : $resource->toBase();

            return $this->collection;
        }
    }

    /**
     * Get the resource that this resource collects.
     *
     * @return string|null
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
