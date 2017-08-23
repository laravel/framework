<?php

namespace Illuminate\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
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
        $this->collection = ($collects = $this->collects())
                    ? $resource->mapInto($collects)
                    : $resource->toBase();

        return $resource instanceof AbstractPaginator
                    ? $resource->setCollection($this->collection)
                    : $this->collection;
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
