<?php

namespace Illuminate\Tests\Integration\Generators;

class NotificationMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Notifications/FooNotification.php',
        'resources/views/foo-notification.blade.php',
        'tests/Feature/Notifications/FooNotificationTest.php',
    ];

    public function testItCanGenerateNotificationFile()
    {
        $this->artisan('make:notification', ['name' => 'FooNotification'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Notifications;',
            'use Illuminate\Notifications\Notification;',
            'class FooNotification extends Notification',
            'return (new MailMessage)',
        ], 'app/Notifications/FooNotification.php');

        $this->assertFilenameNotExists('resources/views/foo-notification.blade.php');
        $this->assertFilenameNotExists('tests/Feature/Notifications/FooNotificationTest.php');
    }

    public function testItCanGenerateNotificationFileWithMarkdownOption()
    {
        $this->artisan('make:notification', ['name' => 'FooNotification', '--markdown' => 'foo-notification'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Notifications;',
            'class FooNotification extends Notification',
            "return (new MailMessage)->markdown('foo-notification')",
        ], 'app/Notifications/FooNotification.php');

        $this->assertFileContains([
            '<x-mail::message>',
        ], 'resources/views/foo-notification.blade.php');
    }

    public function testItCanGenerateNotificationFileWithTest()
    {
        $this->artisan('make:notification', ['name' => 'FooNotification', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Notifications/FooNotification.php');
        $this->assertFilenameNotExists('resources/views/foo-notification.blade.php');
        $this->assertFilenameExists('tests/Feature/Notifications/FooNotificationTest.php');
    }
}
