<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\TestCase;

class AuthListenersSendEmailVerificationNotificationHandleFunctionTest extends TestCase
{
    /**
     * @return void
     */
    public function testWillExecuted()
    {
        $user = $this->unverifiedUser();

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));

        $this->assertTrue($user->notificationSent);
    }

    /**
     * @return void
     */
    public function testUserIsNotInstanceOfMustVerifyEmail()
    {
        $user = new User;

        $listener = new SendEmailVerificationNotification;

        // The listener would fatal by calling an undefined method if it
        // failed to skip a user that doesn't implement MustVerifyEmail.
        $listener->handle(new Registered($user));

        $this->addToAssertionCount(1);
    }

    /**
     * @return void
     */
    public function testHasVerifiedEmailAsTrue()
    {
        $user = $this->unverifiedUser();
        $user->verified = true;

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));

        $this->assertFalse($user->notificationSent);
    }

    protected function unverifiedUser()
    {
        return new class implements MustVerifyEmail
        {
            public $verified = false;

            public $notificationSent = false;

            public function hasVerifiedEmail()
            {
                return $this->verified;
            }

            public function markEmailAsVerified()
            {
                $this->verified = true;
            }

            public function markEmailAsUnverified()
            {
                $this->verified = false;
            }

            public function sendEmailVerificationNotification()
            {
                $this->notificationSent = true;
            }

            public function getEmailForVerification()
            {
                return 'test@example.com';
            }
        };
    }
}
