<?php

namespace Illuminate\Tests\Integration\Generators;

class ViewMakeCommandTest extends TestCase
{
    protected $files = [
        'resources/views/foo.blade.php',
        'tests/Feature/View/FooTest.php',
    ];

    public function testItCanGenerateViewFile()
    {
        $this->artisan('make:view', ['name' => 'foo'])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo.blade.php');
        $this->assertFilenameNotExists('tests/Feature/View/FooTest.php');
    }

    public function testItCanGenerateViewFileWithTest()
    {
        $this->artisan('make:view', ['name' => 'foo', '--test' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('resources/views/foo.blade.php');
        $this->assertFilenameExists('tests/Feature/View/FooTest.php');
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

        $this->assertFilenameExists('app/Mail/MyFooMail.php');
        $this->assertFilenameExists('resources/views/mail/my-foo-mail.blade.php');
    }
}
