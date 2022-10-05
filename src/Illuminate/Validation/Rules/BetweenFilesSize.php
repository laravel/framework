<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class BetweenFilesSize implements Rule
{
    protected int $maxSize;
    protected int $minSize;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(int $minSize, int $maxSize)
    {
        $this->minSize = $minSize * 1024;
        $this->maxSize = $maxSize * 1024;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $totalSize = 0;
        foreach ($value as $file) {
            $totalSize += filesize($file);
        }

        return $totalSize >= $this->minSize && $totalSize <= $this->maxSize;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The Files Size Should be between '.$this->minSize / 1048576 .' MegaByte and '.$this->maxSize / 1048576 .' MegaByte';
    }
}
