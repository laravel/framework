<?php

namespace Illuminate\Database\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Casts\AsBinary;

trait HasBinaryIds
{
    /**
     * Indicates that the model uses binary IDs.
     *
     * @var bool
     */
    public $usesBinaryIds = true;

    /**
     * Get the binary ID format used by the model.
     *
     * @return string
     */
    abstract public function getBinaryIdFormat(): string;

    /**
     * Initialize the HasBinaryIds trait for the model.
     *
     * @return void
     */
    public function initializeHasBinaryIds(): void
    {
        foreach ($this->getBinaryIds() as $field => $format) {
            $this->mergeCasts([$field => AsBinary::of($format)]);
        }
    }

    /**
     * Get the binary ID fields and their formats.
     *
     * @return array<string, string>
     */
    public function getBinaryIds(): array
    {
        return [$this->getKeyName() => $this->getBinaryIdFormat()];
    }
}
