<?php

namespace Illuminate\Contracts\Validation;

interface AiModerationVerifier
{
    /**
     * Verify that the given content passes AI moderation.
     *
     * @param  array{value: string, categories: array<string>, threshold: float, provider: string|null}  $data
     * @return \Illuminate\Contracts\Validation\AiModerationResult
     */
    public function verify(array $data): AiModerationResult;
}
