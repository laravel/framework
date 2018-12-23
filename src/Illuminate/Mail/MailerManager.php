<?php
declare(strict_types=1);

namespace Illuminate\Mail;

use Illuminate\Events\Dispatcher;
use Illuminate\View\Factory;
use Swift_Mailer;
use Aws\Ses\SesClient;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use InvalidArgumentException;
use Illuminate\Log\LogManager;
use Illuminate\Support\Manager;
use GuzzleHttp\Client as HttpClient;
use Swift_SmtpTransport as SmtpTransport;
use Illuminate\Mail\Transport\LogTransport;
use Illuminate\Mail\Transport\SesTransport;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Mail\Transport\MailgunTransport;
use Illuminate\Mail\Transport\MandrillTransport;
use Illuminate\Mail\Transport\SparkPostTransport;
use Swift_SendmailTransport as SendmailTransport;
use Illuminate\Contracts\Mail\Mailer as MailerContract;
use Illuminate\Contracts\Queue\Factory as QueueContract;
use Illuminate\Contracts\Mail\Mailable as MailableContract;
use Illuminate\Contracts\Mail\MailQueue as MailQueueContract;

/**
 * Class MailerManager
 * @package Illuminate\Mail
 * @method \Illuminate\Mail\Mailer driver(string $driver = null)
 */
class MailerManager extends Manager implements MailerContract, MailQueueContract
{
    /**
     * @var \Illuminate\View\Factory
     */
    protected $views;

    /**
     * @var \Illuminate\Events\Dispatcher|null
     */
    protected $events;

    /**
     * @var QueueContract|null
     */
    protected $queue;

    /**
     * MailerManager constructor.
     *
     * @param $app
     * @param \Illuminate\View\Factory $views
     * @param \Illuminate\Events\Dispatcher|null $events
     */
    public function __construct($app, Factory $views,  Dispatcher $events = null)
    {
        parent::__construct($app);

        $this->views = $views;
        $this->events = $events;
    }

    /**
     * Create a new driver instance.
     *
     * @param  string  $driver
     * @return \Illuminate\Mail\Mailer
     *
     * @throws \InvalidArgumentException
     */
    protected function createDriver($driver)
    {
        $globalConfig = $this->app['config']->get('mail');
        $config = $this->app['config']->get("mail.mailers.{$driver}");

        if(!empty($config)) {
            $transport = $this->createTransport($config['driver'], $config);

            $mailer = new Mailer(
                $this->views,
                new Swift_Mailer($transport),
                $this->events,
                $driver
            );

            if($this->queue) {
                $mailer->setQueue($this->queue);
            }


            // Next we will set all of the addresses on this mailer, which allows
            // for easy unification of all "from" addresses as well as easy debugging
            // of sent messages since they get be sent into a single email address.
            foreach (['from', 'reply_to', 'to'] as $type) {
                $address = $globalConfig[$type] ?? $config[$type] ?? null;
                if (is_array($address) && isset($address['address'])) {
                    $mailer->{'always'.Str::studly($type)}($address['address'], $address['name']);
                }
            }

            return $mailer;
        }

        throw new InvalidArgumentException("Driver [$driver] not found.");
    }

    /**
     * @param  string $driver
     * @param  array $config
     * @return \Swift_Transport
     */
    protected function createTransport($driver, $config)
    {
        if (isset($this->customCreators[$driver])) {
            return $this->customCreators[$driver]($this->app, $config);
        } else {
            $method = 'create'.Str::studly($driver).'Transport';

            if (method_exists($this, $method)) {
                return $this->$method($config);
            }
        }

        throw new InvalidArgumentException("Transport [$driver] not supported.");
    }


    /**
     * Create an instance of the SMTP Swift Transport driver.
     *
     * @param  array $config
     * @return \Swift_SmtpTransport
     */
    protected function createSmtpTransport(array $config)
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
    protected function createSendmailTransport(array $config)
    {
        return new SendmailTransport($config['sendmail'] ?? $this->app['config']['mail']['sendmail']);
    }

    /**
     * Create an instance of the Amazon SES Swift Transport driver.
     *
     * @param array $config
     * @return \Illuminate\Mail\Transport\SesTransport
     */
    protected function createSesTransport(array $config)
    {
        $config = array_merge($this->app['config']->get('services.ses', []), $config, [
            'version' => 'latest', 'service' => 'email',
        ]);

        return new SesTransport(
            new SesClient($this->addSesCredentials($config)),
            $config['options'] ?? []
        );
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
            $config['credentials'] = Arr::only($config, ['key', 'secret', 'token']);
        }

        return $config;
    }

    /**
     * Create an instance of the Mail Swift Transport driver.
     *
     * @return \Swift_SendmailTransport
     */
    protected function createMailTransport()
    {
        return new SendmailTransport;
    }

    /**
     * Create an instance of the Mailgun Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\MailgunTransport
     */
    protected function createMailgunTransport(array $config)
    {
        $config = array_merge($this->app['config']->get('services.mailgun', []), $config);

        return new MailgunTransport(
            $this->guzzle($config),
            $config['secret'],
            $config['domain'],
            $config['endpoint'] ?? null
        );
    }

    /**
     * Create an instance of the Mandrill Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\MandrillTransport
     */
    protected function createMandrillTransport(array $config)
    {
        $config = array_merge($this->app['config']->get('services.mandrill', []), $config);

        return new MandrillTransport(
            $this->guzzle($config), $config['secret']
        );
    }

    /**
     * Create an instance of the SparkPost Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\SparkPostTransport
     */
    protected function createSparkPostTransport(array $config)
    {
        $config = array_merge($this->app['config']->get('services.sparkpost', []), $config);

        return new SparkPostTransport(
            $this->guzzle($config), $config['secret'], $config['options'] ?? []
        );
    }

    /**
     * Create an instance of the Log Swift Transport driver.
     *
     * @param  array $config
     * @return \Illuminate\Mail\Transport\LogTransport
     */
    protected function createLogTransport(array $config)
    {
        $logger = $this->app->make(LoggerInterface::class);

        if ($logger instanceof LogManager) {
            $logger = $logger->channel($config['log_channel']);
        }

        return new LogTransport($logger);
    }

    /**
     * Create an instance of the Array Swift Transport Driver.
     *
     * @return \Illuminate\Mail\Transport\ArrayTransport
     */
    protected function createArrayTransport()
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

    /**
     * Queue a new e-mail message for sending.
     *
     * @param  string|array|\Illuminate\Contracts\Mail\Mailable $view
     * @param  string $queue
     * @return mixed
     */
    public function queue($view, $queue = null)
    {
        return $this->driver()->queue($view, $queue);
    }

    /**
     * Queue a new e-mail message for sending after (n) seconds.
     *
     * @param  \DateTimeInterface|\DateInterval|int $delay
     * @param  string|array|\Illuminate\Contracts\Mail\Mailable $view
     * @param  string|null $queue
     * @param  string|null $driver
     * @return mixed
     */
    public function later($delay, $view, $queue = null, $driver = null)
    {
        if (! $view instanceof MailableContract) {
            throw new InvalidArgumentException('Only mailables may be queued.');
        }

        return $view->later($delay, is_null($queue) ? $this->queue : $queue, $driver);
        return $this->driver()->later($delay, $view, $queue);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function to($users)
    {
        return $this->driver()->to($users);
    }

    /**
     * Begin the process of mailing a mailable class instance.
     *
     * @param  mixed $users
     * @return \Illuminate\Mail\PendingMail
     */
    public function bcc($users)
    {
        return $this->driver()->bcc($users);
    }

    /**
     * Send a new message when only a raw text part.
     *
     * @param  string $text
     * @param  mixed $callback
     * @return void
     */
    public function raw($text, $callback)
    {
        return $this->driver()->raw($text, $callback);
    }

    /**
     * Send a new message using a view.
     *
     * @param  string|array|\Illuminate\Contracts\Mail\Mailable $view
     * @param  array $data
     * @param  \Closure|string $callback
     * @return void
     */
    public function send($view, array $data = [], $callback = null)
    {
        return $this->driver()->send($view,  $data, $callback);
    }

    /**
     * Get the array of failed recipients.
     *
     * @return array
     */
    public function failures()
    {
        return $this->driver()->failures();
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->app['config']['mail.default'];
    }

    /**
     * Set the queue manager instance.
     *
     * @param  \Illuminate\Contracts\Queue\Factory  $queue
     * @return $this
     */
    public function setQueue(QueueContract $queue)
    {
        $this->queue = $queue;

        return $this;
    }
}
