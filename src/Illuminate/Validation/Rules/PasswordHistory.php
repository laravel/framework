<?php

namespace Illuminate\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordHistory implements ValidationRule
{
    /**
     * Create a new rule instance.
     */
    public function __construct(
        protected $user,
        protected int $count = 5
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $recentHashes = $this->user
            ->passwordHistories()
            ->latest()
            ->take($this->count)
            ->pluck('password_hash')
            ->toArray();

        foreach ($recentHashes as $hash) {
            // Use PHP's native password_verify function
            if (password_verify($value, $hash)) {
                $fail('password.history');

                return;
            }
        }
    }
}
