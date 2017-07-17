<?php

namespace Illuminate\Http\Concerns;

trait HandlesPreconditions
{
    /**
     * Checks if the request passes preconditions.
     *
     * @param  string|null  $eTag
     * @param  string|null  $lastModified
     * @return bool
     */
    public function passesPreconditions($eTag = null, $lastModified = null)
    {
        if ($eTag === null && $lastModified === null) {
            return true;
        }

        if ($eTag !== null && $eTags = $this->getETags()) {
            if (in_array($eTag, $eTags) === false && in_array('*', $eTags) === false) {
                return false;
            }
        }

        if ($lastModified !== null && $modifiedSince = $this->headers->get('If-Modified-Since')) {
            if (strtotime($lastModified) > strtotime($modifiedSince)) {
                return false;
            }
        }

        return true;
    }
}
