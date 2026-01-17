<?php

namespace Illuminate\Contracts\Validation;

interface AiModerationResult
{
    /**
     * Determine if the content was flagged by moderation.
     *
     * @return bool
     */
    public function flagged(): bool;

    /**
     * Get the categories that were flagged.
     *
     * @return array<string>
     */
    public function flaggedCategories(): array;

    /**
     * Get the scores for each category.
     *
     * @return array<string, float>
     */
    public function scores(): array;
}
