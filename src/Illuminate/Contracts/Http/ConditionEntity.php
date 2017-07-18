<?php

namespace Illuminate\Contracts\Http;

interface ConditionEntity
{
    /**
     * Get the entity tag.
     *
     * @return string|null
     */
    public function getEtag();

    /**
     * Get the last modified date.
     *
     * @return \DateTime|null
     */
    public function getLastModified();
}
