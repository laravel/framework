<?php

namespace Illuminate\Validation\Rules;

use Illuminate\Contracts\Validation\Rule;

class MinFilesSize implements Rule
{
    protected int $minSize;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($minSize)
    {
        $this->minSize = $minSize * 1024;
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

        return $totalSize >= $this->minSize;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return 'The Files Size Should be more than or equal ' . $this->minSize / 1048576 . ' MegaByte';
    }
}
