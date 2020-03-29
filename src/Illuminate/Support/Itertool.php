<?php


namespace Illuminate\Support;


class Itertool
{
    private $items;

    public function __construct($items)
    {
        $this->items = $items;
    }

    public function combinations(int $n)
    {
        $size = count($this->items);
        if ($n <= 0 || $n > $size) {
            return;
        }
        $keys = range(0, $n - 1);
        do {
            yield $this->only($keys);
        } while ($this->nextCombination($keys, $size));
    }


    private function only($keys)
    {
        $a = [];
        foreach ($keys as $v) {
            $a[] = $this->items[$v];
        }
        return $a;
    }

    private function nextCombination(&$iter, $size)
    {
        $i = 0;
        $n = count($iter);
        $j = $n - 1 - $i;
        $carry = 0;
        while ($j >= 0) {
            $iter[$j] += 1;
            $carry = $iter[$j] == $size - $i ? 1 : 0;
            if ($carry == 0) {
                break;
            }
            $i += 1;
            $j = $n - 1 - $i;
        }
        if ($carry == 0 && $j >= 0) {
            $j += 1;
            for (; $j < $n; $j++) {
                $iter[$j] = $iter[$j - 1] + 1;
            }
            return true;
        } else {
            return false;
        }
    }

    public function permutations()
    {
        if (count($this->items) == 0) {
            return;
        } else if (count($this->items) == 1) {
            yield $this->items;
            return;
        }
        $size = count($this->items);
        $keys = range(0, $size - 1);
        while (true) {
            yield $this->only($keys);
            $i = $size - 2;
            while ($i >= 0 && $keys[$i] > $keys[$i + 1])
                $i--;
            if ($i < 0)
                break;
            $j = $size - 1;
            while ($keys[$i] > $keys[$j])
                $j--;
            self::swap($keys, $i, $j);
            self::reverse($keys, $i + 1, $size - 1);
        }
    }

    private static function swap(&$items, int $i, int $j)
    {
        $tmp = $items[$i];
        $items[$i] = $items[$j];
        $items[$j] = $tmp;
    }

    private static function reverse(&$items, int $from, int $to)
    {
        while ($from < $to) {
            self::swap($items, $from++, $to--);
        }
    }
}
