<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Validation\Rules\PasswordHistory;
use PHPUnit\Framework\TestCase;

class ValidationPasswordHistoryRuleTest extends TestCase
{
    /**
     * Test that password history rule fails for recently used password
     */
    public function test_password_history_rule_fails_for_recent_password()
    {
        // Use bcrypt to create test hash
        $oldPasswordHash = password_hash('oldPassword123', PASSWORD_BCRYPT);

        // Create a mock user with password history
        $mockUser = new class($oldPasswordHash) {
            public function __construct(private $hash) {}

            public function passwordHistories()
            {
                return new class($this->hash) {
                    public function __construct(private $hash) {}

                    public function latest()
                    {
                        return $this;
                    }

                    public function take($count)
                    {
                        return $this;
                    }

                    public function pluck($column)
                    {
                        return collect([$this->hash]);
                    }
                };
            }
        };

        // Create the rule
        $rule = new PasswordHistory($mockUser, 1);

        // Test that old password fails validation
        $failures = [];
        $rule->validate('password', 'oldPassword123', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        // Assert that validation failed
        $this->assertNotEmpty($failures, 'Password history rule should fail for recent password');
    }

    /**
     * Test that password history rule passes for new password
     */
    public function test_password_history_rule_passes_for_new_password()
    {
        // Create a mock user with no password history
        $mockUser = new class {
            public function passwordHistories()
            {
                return new class {
                    public function latest()
                    {
                        return $this;
                    }

                    public function take($count)
                    {
                        return $this;
                    }

                    public function pluck($column)
                    {
                        // Empty history
                        return collect([]);
                    }
                };
            }
        };

        // Create the rule
        $rule = new PasswordHistory($mockUser, 1);

        // Test that new password passes validation
        $failures = [];
        $rule->validate('password', 'brandNewPassword456!', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        // Assert that validation passed (no failures)
        $this->assertEmpty($failures, 'Password history rule should pass for new password');
    }

    /**
     * Test password history rule with multiple previous passwords
     */
    public function test_password_history_rule_with_multiple_passwords()
    {
        // Create hashes for multiple passwords
        $passwordHashes = [
            password_hash('password1', PASSWORD_BCRYPT),
            password_hash('password2', PASSWORD_BCRYPT),
            password_hash('password3', PASSWORD_BCRYPT),
            password_hash('password4', PASSWORD_BCRYPT),
            password_hash('password5', PASSWORD_BCRYPT),
        ];

        // Create a mock user with multiple password hashes
        $mockUser = new class($passwordHashes) {
            public function __construct(private $hashes) {}

            public function passwordHistories()
            {
                return new class($this->hashes) {
                    public function __construct(private $hashes) {}

                    public function latest()
                    {
                        return $this;
                    }

                    public function take($count)
                    {
                        return $this;
                    }

                    public function pluck($column)
                    {
                        return collect($this->hashes);
                    }
                };
            }
        };

        // Create rule to check last 5 passwords
        $rule = new PasswordHistory($mockUser, 5);

        // Test that one of the old passwords fails
        $failures = [];
        $rule->validate('password', 'password3', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        // Should fail because password3 is in history
        $this->assertNotEmpty($failures, 'Should fail for password in history');

        // Test that completely new password passes
        $failures = [];
        $rule->validate('password', 'completelyNewPassword!', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        // Should pass
        $this->assertEmpty($failures, 'Should pass for new password');
    }

    /**
     * Test that empty password history allows any password
     */
    public function test_password_history_rule_passes_with_empty_history()
    {
        $mockUser = new class {
            public function passwordHistories()
            {
                return new class {
                    public function latest()
                    {
                        return $this;
                    }

                    public function take($count)
                    {
                        return $this;
                    }

                    public function pluck($column)
                    {
                        return collect([]);  // No history
                    }
                };
            }
        };

        $rule = new PasswordHistory($mockUser, 5);

        $failures = [];
        $rule->validate('password', 'anyPassword123', function ($message) use (&$failures) {
            $failures[] = $message;
        });

        $this->assertEmpty($failures, 'Should pass when no password history exists');
    }
}