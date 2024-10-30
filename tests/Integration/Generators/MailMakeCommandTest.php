<?php

namespace Illuminate\Tests\Integration\Generators;

class MailMakeCommandTest extends TestCase
{
    protected $files = [
        'app/Mail/*.php',
        'resources/views/foo-mail.blade.php',
        'resources/views/mail/*.blade.php',
        'tests/Feature/Mail/*.php',
    ];

    public function testItCanGenerateMailFile()
    {
        $this->artisan('make:mail', ['name' => 'FooMail'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Mail;',
            'use Illuminate\Mail\Mailable;',
            'class FooMail extends Mailable',
        ], 'app/Mail/FooMail.php');

        $this->assertFilenameNotExists('resources/views/foo-mail.blade.php');
        $this->assertFilenameNotExists('tests/Feature/Mail/FooMailTest.php');
    }

    public function testItCanGenerateMailFileWithMarkdownOption()
    {
        $this->artisan('make:mail', ['name' => 'FooMail', '--markdown' => 'foo-mail'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Mail;',
            'use Illuminate\Mail\Mailable;',
            'class FooMail extends Mailable',
            'return new Content(',
            "markdown: 'foo-mail',",
        ], 'app/Mail/FooMail.php');

        $this->assertFileContains([
            '<x-mail::message>',
            '<x-mail::button :url="\'\'">',
            '</x-mail::button>',
            '</x-mail::message>',
        ], 'resources/views/foo-mail.blade.php');
    }

    public function testErrorsWillBeDisplayedWhenMarkdownsAlreadyExist()
    {
        $existingMarkdownPath = 'resources/views/existing-markdown.blade.php';
        $this->app['files']
            ->put(
                $this->app->basePath($existingMarkdownPath),
                '<x-mail::message>My existing markdown</x-mail::message>'
            );
        $this->artisan('make:mail', ['name' => 'FooMail', '--markdown' => 'existing-markdown'])
            ->expectsOutputToContain('already exists.')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Mail;',
            'use Illuminate\Mail\Mailable;',
            'class FooMail extends Mailable',
            'return new Content(',
            "markdown: 'existing-markdown',",
        ], 'app/Mail/FooMail.php');
        $this->assertFileContains([
            '<x-mail::message>',
            'My existing markdown',
            '</x-mail::message>',
        ], $existingMarkdownPath);
    }

    public function testItCanGenerateMailFileWithViewOption()
    {
        $this->artisan('make:mail', ['name' => 'FooMail', '--view' => 'foo-mail'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Mail;',
            'use Illuminate\Mail\Mailable;',
            'class FooMail extends Mailable',
            'return new Content(',
            "view: 'foo-mail',",
        ], 'app/Mail/FooMail.php');

        $this->assertFilenameExists('resources/views/foo-mail.blade.php');
    }

    public function testErrorsWillBeDisplayedWhenViewsAlreadyExist()
    {
        $existingViewPath = 'resources/views/existing-template.blade.php';
        $this->app['files']
            ->put(
                $this->app->basePath($existingViewPath),
                '<div>My existing template</div>'
            );
        $this->artisan('make:mail', ['name' => 'FooMail', '--view' => 'existing-template'])
            ->expectsOutputToContain('already exists.')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace App\Mail;',
            'use Illuminate\Mail\Mailable;',
            'class FooMail extends Mailable',
            'return new Content(',
            "view: 'existing-template',",
        ], 'app/Mail/FooMail.php');
        $this->assertFilenameExists($existingViewPath);
        $this->assertFileContains([
            '<div>My existing template</div>',
        ], $existingViewPath);
    }

    public function testItCanGenerateMailFileWithTest()
    {
        $this->artisan('make:mail', ['name' => 'FooMail', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Mail/FooMail.php');
        $this->assertFilenameNotExists('resources/views/foo-mail.blade.php');
        $this->assertFilenameExists('tests/Feature/Mail/FooMailTest.php');
    }

    public function testItCanGenerateMailWithNoInitialInput()
    {
        $this->artisan('make:mail')
            ->expectsQuestion('What should the mailable be named?', 'FooMail')
            ->expectsQuestion('Would you like to create a view?', 'none')
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Mail/FooMail.php');
        $this->assertFilenameDoesNotExists('resources/views/mail/foo-mail.blade.php');
    }

    public function testItCanGenerateMailWithViewWithNoInitialInput()
    {
        $this->artisan('make:mail')
            ->expectsQuestion('What should the mailable be named?', 'MyFooMail')
            ->expectsQuestion('Would you like to create a view?', 'view')
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Mail/MyFooMail.php');
        $this->assertFilenameExists('resources/views/mail/my-foo-mail.blade.php');
    }

    public function testItCanGenerateMailWithMarkdownViewWithNoInitialInput()
    {
        $this->artisan('make:mail')
            ->expectsQuestion('What should the mailable be named?', 'FooMail')
            ->expectsQuestion('Would you like to create a view?', 'markdown')
            ->assertExitCode(0);

        $this->assertFilenameExists('app/Mail/FooMail.php');
        $this->assertFilenameExists('resources/views/mail/foo-mail.blade.php');
    }
}
