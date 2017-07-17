<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait HandlesConditions
{
    /**
     * Get the entity tag.
     *
     * @return string|null
     */
    public function getEtag()
    {
        if (! $this->exists) {
            return;
        }

        return sha1(implode('|', [
            $this->getQueueableId(),
            $this->getQueueableConnection(),
            $this->toJson(),
        ]));
    }

    /**
     * Get the last modified date.
     *
     * @return mixed
     */
    public function getLastModified()
    {
        if (! $this->exists) {
            return;
        }

        return $this->{$this->getUpdatedAtColumn()};
    }
}
