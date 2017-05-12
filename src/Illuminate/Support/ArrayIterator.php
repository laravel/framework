<?php

namespace Illuminate\Support;

use ArrayIterator as BaseArrayIterator;

class ArrayIterator extends BaseArrayIterator
{
    private $i = 0;

    public function rewind()
    {
        $this->i = 0;

        return parent::rewind();
    }

    public function next()
    {
        $this->i++;

        return parent::next();
    }

    public function prev()
    {
        $this->i--;

        return parent::prev();
    }

    public function alternator($even = 'even', $odd = 'odd')
    {
        return $this->i === 0 || $this->i % 2 === 0 ? $even : $odd;
    }

    public function isFirst($returnIfTrue = true, $returnIfFalse = false)
    {
        if($this->i === 0) {

            return $returnIfTrue;
        }

        return $returnIfFalse;
    }

    public function isLast($returnIfTrue = true, $returnIfFalse = false)
    {
        if($this->count() === $this->i + 1) {

            return $returnIfTrue;
        }

        return $returnIfFalse;
    }

    public function currentIndex()
    {
        return $this->i;
    }

    public function __toString()
    {
        return (string) $this->i;
    }
}