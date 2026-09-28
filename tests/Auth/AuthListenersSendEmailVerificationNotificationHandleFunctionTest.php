<?php

namespace Illuminate\Tests\Auth;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
        // Has the verification methods but doesn't implement MustVerifyEmail.
        $user = new class
        {
            public $notificationSent = false;

            public function hasVerifiedEmail()
            {
                return false;
            }

            public function sendEmailVerificationNotification()
            {
                $this->notificationSent = true;
            }
        };

        $listener = new SendEmailVerificationNotification;

        $listener->handle(new Registered($user));

        $this->assertFalse($user->notificationSent);
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
