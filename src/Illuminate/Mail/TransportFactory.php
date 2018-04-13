<?php

namespace Illuminate\Mail;

use Aws\Ses\SesClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use GuzzleHttp\Client as HttpClient;
use Swift_SmtpTransport as SmtpTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Mail\Transport\ArrayTransport;
use Swift_SendmailTransport as MailTransport;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Mail\Transport\MandrillTransport;
use Illuminate\Mail\Transport\SparkPostTransport;
use Swift_SendmailTransport as SendmailTransport;

class TransportFactory
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Create a new factory instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Create a new transport instance.
     *
     * @param  string $driver
     * @param  array  $config
     * @return \Swift_Transport
     *
     * @throws \InvalidArgumentException
     */
    public function create($driver, array $config)
    {
        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method($config);
        }

        throw new InvalidArgumentException("Unsupported driver [{$driver}]");
    }

    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * @param  array $config
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpDriver(array $config)
    {
        // The Swift SMTP transport instance will allow us to use any SMTP backend
        // for delivering mail such as Sendgrid, Amazon SES, or a custom server
        // a developer has available. We will just pass this configured host.
        $transport = new SmtpTransport($config['host'], $config['port']);

        if (isset($config['encryption'])) {
            $transport->setEncryption($config['encryption']);
        }

        // Once we have the transport we will check for the presence of a username
        // and password. If we have it we will set the credentials on the Swift
        // transporter instance so that we'll properly authenticate delivery.
        if (isset($config['username'])) {
            $transport->setUsername($config['username']);

            $transport->setPassword($config['password']);
        }

        // Next we will set any stream context options specified for the transport
        // and then return it. The option is not required any may not be inside
        // the configuration array at all so we'll verify that before adding.
        if (isset($config['stream'])) {
            $transport->setStreamOptions($config['stream']);
        }

        return $transport;
    }

    /**
     * Create an instance of the Sendmail Swift Transport driver.
     *
     * @param  array $config
     * @return \Swift_SendmailTransport
     */
    protected function createSendmailDriver(array $config)
    {
        return new SendmailTransport($config['path']);
    }

    /**
     * Create an instance of the Amazon SES Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\SesTransport
     */
    protected function createSesDriver(array $config)
    {
        $config = array_merge($config['service'], [
            'version' => 'latest', 'service' => 'email',
        ]);

        return new SesTransport(new SesClient(
            $this->addSesCredentials($config)
        ));
    }

    /**
     * Add the SES credentials to the configuration array.
     *
     * @param  array  $config
     * @return array
     */
    protected function addSesCredentials(array $config)
    {
        if ($config['key'] && $config['secret']) {
            $config['credentials'] = Arr::only($config, ['key', 'secret']);
        }

        return $config;
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
     *
     * @return \Swift_SendmailTransport
     */
    protected function createMailDriver()
    {
        return new MailTransport;
    }

    /**
     * Create an instance of the Mailgun Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\MailgunTransport
     */
    protected function createMailgunDriver(array $config)
    {
        return new MailgunTransport(
            $this->guzzle($config['service']),
            $config['service']['secret'], $config['service']['domain']
        );
    }

    /**
     * Create an instance of the Mandrill Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\MandrillTransport
     */
    protected function createMandrillDriver(array $config)
    {
        return new MandrillTransport(
            $this->guzzle($config['service']), $config['service']['secret']
        );
    }

    /**
     * Create an instance of the SparkPost Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\SparkPostTransport
     */
    protected function createSparkPostDriver(array $config)
    {
        return new SparkPostTransport(
            $this->guzzle($config['service']), $config['service']['secret'], $config['service']['options'] ?? []
        );
    }

    /**
     * Create an instance of the Log Swift Transport driver.
     *
     * @return \Illuminate\Mail\Transport\LogTransport
     */
    protected function createLogDriver()
    {
        return new LogTransport($this->app->make(LoggerInterface::class));
    }

    /**
     * Create an instance of the Array Swift Transport Driver.
     *
     * @return \Illuminate\Mail\Transport\ArrayTransport
     */
    protected function createArrayDriver()
    {
        return new ArrayTransport;
    }

    /**
     * Get a fresh Guzzle HTTP client instance.
     *
     * @param  array  $config
     * @return \GuzzleHttp\Client
     */
    protected function guzzle($config)
    {
        return new HttpClient(Arr::add(
            $config['guzzle'] ?? [], 'connect_timeout', 60
        ));
    }
}
