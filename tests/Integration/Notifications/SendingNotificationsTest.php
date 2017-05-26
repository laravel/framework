<?php

use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Notifications\Channels\MailChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

/**
 * @group integration
 */
class SendingNotificationsTest extends TestCase
{
    public $mailer;

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->mailer = Mockery::mock(Mailer::class);

        $app->bind(Mailer::class, function(){
            return $this->mailer;
        });
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });
    }

    public function test_mail_channel_by_class_name()
    {
        $this->mailer->shouldReceive('send')->once();

        $user = NotifiableUser::forceCreate([
            'email' => 'taylor@laravel.com',
        ]);


        $user->notify(new TestMailNotification());
    }
}

class NotifiableUser extends Model
{
    use Notifiable;

    public $table = 'users';
    public $timestamps = false;
}

class TestMailNotification extends Notification
{
    public function via($notifiable)
    {
        return [MailChannel::class];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.');
    }
}
