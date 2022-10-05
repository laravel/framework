<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class MaxFilesSize implements Rule
{
    protected int $maxSize;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($maxSize)
    {
        $this->maxSize = $maxSize * 1024;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        $totalSize = 0;
        foreach ($value as $file) {
            $totalSize += filesize($file);
        }

        return $totalSize <= $this->maxSize;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The Files Size Should be less than or equal ' . $this->maxSize / 1048576 . ' MegaByte';
    }
}
