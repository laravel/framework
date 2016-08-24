<?php

namespace Illuminate\Container\Traits;

trait TagsTrait
{
	private $tags = [];

    /**
     * Assign a set of tags to a given binding.
     *
     * @param  array|string  $abstracts
     * @param  array|mixed   ...$tags
     * @return void
     */
    public function tag($abstracts, $tags)
    {
        $tags = (is_array($tags)) ? $tags : array_slice(func_get_args(), 1);
        $abstracts = (is_array($abstracts)) ? $abstracts : [$abstracts];

        foreach ($abstracts as $key => $abstract) {
            $abstracts[$key] = $this->normalize($abstract);
        }
        foreach ($tags as $tagName) {
            if (isset($this->tags[$tagName])) {
                $this->tags[$tagName] = array_merge($this->tags[$tagName], $abstracts);
            } else {
                $this->tags[$tagName] = $abstracts;
            }
        }
    }

    /**
     * Resolve all of the bindings for a given tag.
     *
     * @param  string  $tag
     * @return array
     */
    public function tagged($tag)
    {
        $results = [];

        if (isset($this->tags[$tag])) {
            foreach ($this->tags[$tag] as $abstract) {
                $results[] = $this->make($abstract);
            }
        }

        return $results;
    }

}
