<?php namespace Illuminate\Auth;

use Closure;
use Illuminate\Mail\Mailer;
use Illuminate\Routing\Redirector;

class PasswordBroker {

	/**
	 * The password reminder repository.
	 *
	 * @var Illuminate\Auth\ReminderRepositoryInterface  $reminders
	 */
	protected $reminders;

	/**
	 * The user provider implementation.
	 *
	 * @var Illuminate\Auth\UserProviderInterface
	 */
	protected $users;

	/**
	 * The redirector instance.
	 *
	 * @var Illuminate\Routing\Redirector
	 */
	protected $redirector;

	/**
	 * The mailer instance.
	 *
	 * @var Illuminate\Mail\Mailer
	 */
	protected $mailer;

	/**
	 * The view of the password reminder e-mail.
	 *
	 * @var string
	 */
	protected $reminderView;

	/**
	 * Create a new password broker instance.
	 *
	 * @param  Illuminate\Auth\ReminderRepositoryInterface  $reminders
	 * @param  Illuminate\Auth\UserProviderInterface  $users
	 * @param  Illuminate\Routing\Redirector  $redirector
	 * @param  Illuminate\Mail\Mailer  $mailer
	 * @param  string  $reminderView
	 * @return void
	 */
	public function __construct(ReminderRepositoryInterface $reminders,
                                UserProviderInterface $users,
                                Redirector $redirect,
                                Mailer $mailer,
                                $reminderView)
	{
		$this->users = $users;
		$this->mailer = $mailer;
		$this->redirect = $redirect;
		$this->reminders = $reminders;
		$this->reminderView = $reminderView;
	}

	/**
	 * Send a password reminder to a user.
	 *
	 * @param  array    $credentials
	 * @param  Closure  $callback
	 * @return Illuminate\Http\RedirectResponse
	 */
	public function remind(array $credentials, Closure $callback = null)
	{
		// First we will check to see if we found a user at the given crednetials and
		// if we did not we will redirect back to this current URI with a piece of
		// "flash" data in the session to indicate to the developers the errors.
		$user = $this->getUser($credentials);

		if (is_null($user))
		{
			return $this->makeErrorRedirect();
		}

		// Once we have the reminder token, we are ready to send a message out to the
		// user with a link to reset their password. We will then redirect back to
		// the current URI having nothing set in the session to indicate errors.
		$token = $this->reminders->create($user);

		$this->sendReminder($user, $token, $callback);

		return $this->redirect->refresh();
	}

	/**
	 * Send the password reminder e-mail.
	 *
	 * @param  Illuminate\Auth\RemindableInterface  $user
	 * @param  string   $token
	 * @param  Closure  $callback
	 * @return void
	 */
	public function sendReminder(RemindableInterface $user, $token, Closure $callback)
	{
		// We will use the reminder view that was given to the broker to display the
		// password reminder e-mail. We'll pass a "token" variable into the views
		// so that it may be displayed for an user to click for password reset.
		$view = $this->reminderView;

		return $this->mailer->send($view, compact('token'), function($m) use ($user, $callback)
		{
			$m->to($user->getContactEmail());

			call_user_func($callback, $m, $user);
		});
	}

	/**
	 * Reset the password for the given token.
	 *
	 * @param  string   $token
	 * @param  string   $newPassword
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function reset(array $credentials, Closure $callback = null)
	{
		// If the responses from the validate method is not a user instance, we will
		// assume that it is a redirect and simply return it from this method and
		// the user is properly redirected having an error message on the post.
		$user = $this->validateReset($credentials);

		if ( ! $user instanceof RemindableInterface)
		{
			return $user;
		}

		$pass = $this->getPassword();

		// When we call the callback, we will pass the user and the password for the
		// current request. Then, the callback is responsible for the updating of
		// the users object itself so we do not have to be concerned with that.
		$response = call_user_func($callback, $user, $pass);

		$this->reminders->delete($this->getToken());

		return $response;
	}

	/**
	 * Validate a password reset for the given credentials.
	 *
	 * @param  array  $credenitals
	 * @return Illuminate\Auth\RemindableInterface
	 */
	protected function validateReset(array $credentials)
	{
		if (is_null($user = $this->getUser($credentials)))
		{
			return $this->makeErrorRedirect();
		}
		
		if ( ! $this->validNewPasswords())
		{
			return $this->makeErrorRedirect();
		}

		if ( ! $this->reminders->exists($user, $this->getToken()))
		{
			return $this->makeErrorRedirect();
		}

		return $user;
	}

	/**
	 * Determine if the passwords match for the request.
	 *
	 * @return bool
	 */
	protected function validNewPasswords()
	{
		$password = $this->getPassword();

		return $password and $password == $this->getConfirmedPassword();
	}

	/**
	 * Make an error redirect response.
	 *
	 * @return Illuminate\Http\RedirectResponse
	 */
	protected function makeErrorRedirect()
	{
		return $this->redirect->refresh()->with('error', true);
	}

	/**
	 * Get the user for the given credentials.
	 *
	 * @param  array  $credentials
	 * @return Illuminate\Auth\RemindableInterface
	 */
	public function getUser(array $credentials)
	{
		$user = $this->users->retrieveByCredentials($credentials);

		if ($user and ! $user instanceof RemindableInterface)
		{
			throw new \UnexpectedValueException("User must implement Contactable interface.");
		}

		return $user;
	}

	/**
	 * Get the current request object.
	 *
	 * @return Illuminate\Http\Request
	 */
	protected function getRequest()
	{
		return $this->redirect->getUrlGenerator()->getRequest();
	}

	/**
	 * Get the password for the current request.
	 *
	 * @return string
	 */
	protected function getPassword()
	{
		return $this->getRequest()->input('password');
	}

	/**
	 * Get the confirmed password.
	 *
	 * @return string
	 */
	protected function getConfirmedPassword()
	{
		return $this->getRequest()->input('password_confirmation');
	}

}