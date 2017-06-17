<?php

namespace Tests\Feature;

use App\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ResetsPasswordTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Displays the reset password request form.
     *
     * @return void
     */
    public function testDisplaysPasswordResetRequestForm()
    {
        $response = $this->get('password/reset');

        $response->assertStatus(200);
    }

    /**
     * Sends the password reset email when the user exists.
     *
     * @return void
     */
    public function testSendsPasswordResetEmail()
    {
        $user = factory(User::class)->create();

        $this->expectsNotification($user, ResetPassword::class);

        $response = $this->post('password/email', ['email' => $user->email]);

        $response->assertStatus(302);
    }

    /**
     * Does not send a password reset email when the user does not exist.
     *
     * @return void
     */
    public function testDoesNotSendPasswordResetEmail()
    {
        $this->doesntExpectJobs(ResetPassword::class);

        $this->post('password/email', ['email' => 'invalid@email.com']);
    }

    /**
     * Displays the form to reset a password.
     *
     * @return void
     */
    public function testDisplaysPasswordResetForm()
    {
        $response = $this->get('/password/reset/token');

        $response->assertStatus(200);
    }

    /**
     * Allows a user to reset their password.
     *
     * @return void
     */
    public function testChangesAUsersPassword()
    {
        $user = factory(User::class)->create();

        $token = Password::createToken($user);

        $response = $this->post('/password/reset', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password'
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
