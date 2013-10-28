<?php namespace Illuminate\Auth\Reminders;

use Closure;
use Illuminate\Mail\Mailer;
use Illuminate\Routing\Redirector;
use Illuminate\Auth\UserProviderInterface;

class PasswordBroker {

	/**
	 * The password reminder repository.
	 *
	 * @var \Illuminate\Auth\Reminders\ReminderRepositoryInterface  $reminders
	 */
	protected $reminders;

	/**
	 * The user provider implementation.
	 *
	 * @var \Illuminate\Auth\UserProviderInterface
	 */
	protected $users;

	/**
	 * The redirector instance.
	 *
	 * @var \Illuminate\Routing\Redirector
	 */
	protected $redirector;

	/**
	 * The mailer instance.
	 *
	 * @var \Illuminate\Mail\Mailer
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
	 * @param  \Illuminate\Auth\Reminders\ReminderRepositoryInterface  $reminders
	 * @param  \Illuminate\Auth\UserProviderInterface  $users
	 * @param  \Illuminate\Routing\Redirector  $redirect
	 * @param  \Illuminate\Mail\Mailer  $mailer
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
	 * @return \Illuminate\Http\RedirectResponse
	 */
	public function remind(array $credentials, Closure $callback = null)
	{
		// First we will check to see if we found a user at the given credentials and
		// if we did not we will redirect back to this current URI with a piece of
		// "flash" data in the session to indicate to the developers the errors.
		$user = $this->getUser($credentials);

		if (is_null($user))
		{
			return $this->makeErrorRedirect('user');
		}

		// Once we have the reminder token, we are ready to send a message out to the
		// user with a link to reset their password. We will then redirect back to
		// the current URI having nothing set in the session to indicate errors.
		$token = $this->reminders->create($user);

		$this->sendReminder($user, $token, $callback);

		return $this->redirect->refresh()->with('success', true);
	}

	/**
	 * Send the password reminder e-mail.
	 *
	 * @param  \Illuminate\Auth\Reminders\RemindableInterface  $user
	 * @param  string   $token
	 * @param  Closure  $callback
	 * @return void
	 */
	public function sendReminder(RemindableInterface $user, $token, Closure $callback = null)
	{
		// We will use the reminder view that was given to the broker to display the
		// password reminder e-mail. We'll pass a "token" variable into the views
		// so that it may be displayed for an user to click for password reset.
		$view = $this->reminderView;

		return $this->mailer->send($view, compact('token', 'user'), function($m) use ($user, $callback)
		{
			$m->to($user->getReminderEmail());

			if ( ! is_null($callback)) call_user_func($callback, $m, $user);
		});
	}

	/**
	 * Reset the password for the given token.
	 *
	 * @param  array    $credentials
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function reset(array $credentials, Closure $callback)
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

		// Once we have called this callback, we will remove this token row from the
		// table and return the response from this callback so the user gets sent
		// to the destination given by the developers from the callback return.
		$response = call_user_func($callback, $user, $pass);

		$this->reminders->delete($this->getToken());

		return $response;
	}

	/**
	 * Validate a password reset for the given credentials.
	 *
	 * @param  array  $credenitals
	 * @return \Illuminate\Auth\RemindableInterface
	 */
	protected function validateReset(array $credentials)
	{
		if (is_null($user = $this->getUser($credentials)))
		{
			return $this->makeErrorRedirect('user');
		}

		if ( ! $this->validNewPasswords())
		{
			return $this->makeErrorRedirect('password');
		}

		if ( ! $this->reminders->exists($user, $this->getToken()))
		{
			return $this->makeErrorRedirect('token');
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

		$confirm = $this->getConfirmedPassword();

		return $password and strlen($password) >= 6 and $password == $confirm;
	}

	/**
	 * Make an error redirect response.
	 *
	 * @param  string  $reason
	 * @return \Illuminate\Http\RedirectResponse
	 */
	protected function makeErrorRedirect($reason = '')
	{
		if ($reason != '') $reason = 'reminders.'.$reason;

		return $this->redirect->refresh()->with('error', true)->with('reason', $reason);
	}

	/**
	 * Get the user for the given credentials.
	 *
	 * @param  array  $credentials
	 * @return \Illuminate\Auth\Reminders\RemindableInterface
	 */
	public function getUser(array $credentials)
	{
		$user = $this->users->retrieveByCredentials($credentials);

		if ($user and ! $user instanceof RemindableInterface)
		{
			throw new \UnexpectedValueException("User must implement Remindable interface.");
		}

		return $user;
	}

	/**
	 * Get the current request object.
	 *
	 * @return \Illuminate\Http\Request
	 */
	protected function getRequest()
	{
		return $this->redirect->getUrlGenerator()->getRequest();
	}

	/**
	 * Get the reset token for the current request.
	 *
	 * @return string
	 */
	protected function getToken()
	{
		return $this->getRequest()->input('token');
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

	/**
	 * Get the password reminder repository implementation.
	 *
	 * @return \Illuminate\Auth\Reminders\ReminderRepositoryInterface
	 */
	protected function getRepository()
	{
		return $this->reminders;
	}

}
