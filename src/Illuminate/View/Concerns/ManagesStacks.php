<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;

trait ManagesStacks
{
    /**
     * All of the finished, captured push sections.
     *
     * @var array
     */
    protected $pushes = [];

    /**
     * All of the finished, captured prepend sections.
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The hashes of content pushed or prepended into the stack sections.
     * Populated when used with pushonce or prependonce.
     *
     * @var array
     */
    protected $uniqueContentHashes = [];

    /**
     * The stack of in-progress push sections.
     *
     * @var array
     */
    protected $pushStack = [];

    /**
     * Start injecting content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    /**
     * Start injecting content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPushOnce($section, $content = '')
    {
        if ($content === '') {
            $this->startPush($section);
        } else {
            $this->extendPush($section, $content, true);
        }
    }

    /**
     * Stop injecting content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPush()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPush($last, ob_get_clean());
        });
    }

    /**
     * Stop injecting content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPushOnce()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a pushonce stack without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPush($last, ob_get_clean(), true);
        });
    }

    /**
     * Append content to a given push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPush($section, $content, $once = false)
    {
        if (! isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }

        if ($once && $this->contentInSection($section, $content)) {
            return;
        }

        if (! isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] .= $content;
        }
    }

    /**
     * Start prepending content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPrepend($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPrepend($section, $content);
        }
    }

    /**
     * Start prepending content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPrependOnce($section, $content = '')
    {
        if ($content === '') {
            $this->startPrepend($section);
        } else {
            $this->extendPrepend($section, $content, true);
        }
    }

    /**
     * Stop prepending content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPrepend()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPrepend($last, ob_get_clean());
        });
    }

    /**
     * Stop prepending content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPrependOnce()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a prependonce operation without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPrepend($last, ob_get_clean(), true);
        });
    }

    /**
     * Prepend content to a given stack.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPrepend($section, $content, $once = false)
    {
        if (! isset($this->prepends[$section])) {
            $this->prepends[$section] = [];
        }

        if ($once && $this->contentInSection($section, $content)) {
            return;
        }

        if (! isset($this->prepends[$section][$this->renderCount])) {
            $this->prepends[$section][$this->renderCount] = $content;
        } else {
            $this->prepends[$section][$this->renderCount] = $content.$this->prepends[$section][$this->renderCount];
        }
    }


    /**
     * Check if content exists in stack section. Remember if not added before.
     *
     * @param  string  $section
     * @param  string  $content
     * @return bool
     */
    protected function contentInSection($section, $content)
    {
        if (empty($content)) {
            return true;
        }

        $contentHash = sha1(trim($content));

        if (in_array($contentHash, $this->uniqueContentHashes[$section] ?? [])) {
            return true;
        }

        $this->uniqueContentHashes[$section][] = $contentHash;

        return false;
    }

    /**
     * Get the string contents of a push section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldPushContent($section, $default = '')
    {
        if (! isset($this->pushes[$section]) && ! isset($this->prepends[$section])) {
            return $default;
        }

        $output = '';

        if (isset($this->prepends[$section])) {
            $output .= implode(array_reverse($this->prepends[$section]));
        }

        if (isset($this->pushes[$section])) {
            $output .= implode($this->pushes[$section]);
        }

        return $output;
    }

    /**
     * Flush all of the stacks.
     *
     * @return void
     */
    public function flushStacks()
    {
        $this->pushes = [];
        $this->prepends = [];
        $this->uniqueContentHashes = [];
        $this->pushStack = [];
    }
}
