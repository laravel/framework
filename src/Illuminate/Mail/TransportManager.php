<?php namespace Illuminate\Mail;

use Aws\Ses\SesClient;
use Illuminate\Support\Manager;
use Swift_SmtpTransport as SmtpTransport;
use Swift_MailTransport as MailTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Mail\Transport\MandrillTransport;
use Swift_SendmailTransport as SendmailTransport;

class TransportManager extends Manager {

	/**
	 * Create an instance of the SMTP Swift Transport driver.
	 *
	 * @return \Swift_SmtpTransport
	 */
	protected function createSmtpDriver()
	{
		$config = $this->app['config']['mail'];

		// The Swift SMTP transport instance will allow us to use any SMTP backend
		// for delivering mail such as Sendgrid, Amazon SES, or a custom server
		// a developer has available. We will just pass this configured host.
		$transport = SmtpTransport::newInstance(
			$config['host'], $config['port']
		);

		if (isset($config['encryption']))
		{
			$transport->setEncryption($config['encryption']);
		}

		// Once we have the transport we will check for the presence of a username
		// and password. If we have it we will set the credentials on the Swift
		// transporter instance so that we'll properly authenticate delivery.
		if (isset($config['username']))
		{
			$transport->setUsername($config['username']);

			$transport->setPassword($config['password']);
		}

		return $transport;
	}

	/**
	 * Create an instance of the Sendmail Swift Transport driver.
	 *
	 * @return \Swift_SendmailTransport
	 */
	protected function createSendmailDriver()
	{
		$command = $this->app['config']['mail']['sendmail'];

		return SendmailTransport::newInstance($command);
	}

	/**
	 * Create an instance of the Amazon SES Swift Transport driver.
	 *
	 * @return \Swift_SendmailTransport
	 */
	protected function createSesDriver()
	{
		$sesClient = SesClient::factory($this->app['config']->get('services.ses', []));

		return new SesTransport($sesClient);
	}

	/**
	 * Create an instance of the Mail Swift Transport driver.
	 *
	 * @return \Swift_MailTransport
	 */
	protected function createMailDriver()
	{
		return MailTransport::newInstance();
	}

	/**
	 * Create an instance of the Mailgun Swift Transport driver.
	 *
	 * @return \Illuminate\Mail\Transport\MailgunTransport
	 */
	protected function createMailgunDriver()
	{
		$config = $this->app['config']->get('services.mailgun', array());

		return new MailgunTransport($config['secret'], $config['domain']);
	}

	/**
	 * Create an instance of the Mandrill Swift Transport driver.
	 *
	 * @return \Illuminate\Mail\Transport\MandrillTransport
	 */
	protected function createMandrillDriver()
	{
		$config = $this->app['config']->get('services.mandrill', array());

		return new MandrillTransport($config['secret']);
	}

	/**
	 * Create an instance of the Log Swift Transport driver.
	 *
	 * @return \Illuminate\Mail\Transport\LogTransport
	 */
	protected function createLogDriver()
	{
		return new LogTransport($this->app->make('Psr\Log\LoggerInterface'));
	}

	/**
	 * Get the default cache driver name.
	 *
	 * @return string
	 */
	public function getDefaultDriver()
	{
		return $this->app['config']['mail.driver'];
	}

	/**
	 * Set the default cache driver name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultDriver($name)
	{
		$this->app['config']['mail.driver'] = $name;
	}

}
