<?php

namespace Illuminate\Tests\Foundation;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Console\VendorPublishCommand;
use PHPUnit\Framework\TestCase;

class FoundationVendorPublishCommandTest extends TestCase
{
    private const CHOICES = [
        'All providers and tags',
        '<fg=gray>Provider:</> Filament\Support\SupportServiceProvider',
        '<fg=gray>Provider:</> Spatie\MediaLibrary\MediaLibraryServiceProvider',
        '<fg=gray>Tag:</> ai-config',
        '<fg=gray>Tag:</> air-config',
        '<fg=gray>Tag:</> cashier-config',
        '<fg=gray>Tag:</> config',
        '<fg=gray>Tag:</> mail-config',
        '<fg=gray>Tag:</> medialibrary-config',
        '<fg=gray>Tag:</> medialibrary-migrations',
        '<fg=gray>Tag:</> media-library-views',
        '<fg=gray>Tag:</> mcp-config',
        '<fg=gray>Tag:</> pail-config',
        '<fg=gray>Tag:</> sentry-config',
        '<fg=gray>Tag:</> 配置',
    ];

    public function testItRanksSimilarPublishableChoices(): void
    {
        $this->assertSame([
            '<fg=gray>Provider:</> Spatie\MediaLibrary\MediaLibraryServiceProvider',
            '<fg=gray>Tag:</> medialibrary-config',
            '<fg=gray>Tag:</> medialibrary-migrations',
            '<fg=gray>Tag:</> media-library-views',
        ], $this->command()->searchChoices(self::CHOICES, 'me'));

        $this->assertSame([
            '<fg=gray>Tag:</> medialibrary-config',
        ], $this->command()->searchChoices(self::CHOICES, 'media c'));

        $this->assertSame([
            '<fg=gray>Tag:</> medialibrary-config',
        ], $this->command()->searchChoices(self::CHOICES, 'media library config'));

        $this->assertSame([
            '<fg=gray>Tag:</> medialibrary-config',
        ], $this->command()->searchChoices(self::CHOICES, 'media config'));

        $this->assertSame(
            '<fg=gray>Tag:</> medialibrary-config',
            $this->command()->searchChoices(self::CHOICES, 'medai library config')[0],
        );

        $this->assertSame(
            '<fg=gray>Tag:</> ai-config',
            $this->command()->searchChoices(self::CHOICES, 'ai')[0],
        );

        $this->assertSame(
            '<fg=gray>Provider:</> Spatie\MediaLibrary\MediaLibraryServiceProvider',
            $this->command()->searchChoices(self::CHOICES, 'spatie medialibrary')[0],
        );

        $this->assertSame([], $this->command()->searchChoices(self::CHOICES, 'unrelated'));

        $this->assertSame([
            '<fg=gray>Tag:</> 配置',
        ], $this->command()->searchChoices(self::CHOICES, '配置'));

        $this->assertSame(
            'All providers and tags',
            $this->command()->searchChoices(self::CHOICES, 'all')[0],
        );

        $this->assertSame(
            '<fg=gray>Tag:</> medialibrary-config',
            $this->command()->searchChoices(self::CHOICES, 'tag medialibrary')[0],
        );

        $this->assertSame(
            '<fg=gray>Provider:</> Spatie\MediaLibrary\MediaLibraryServiceProvider',
            $this->command()->searchChoices(self::CHOICES, 'provider spatie')[0],
        );
    }

    public function testItReturnsEveryChoiceForAnEmptySearch(): void
    {
        $this->assertSame(self::CHOICES, $this->command()->searchChoices(self::CHOICES, ''));
    }

    private function command(): VendorPublishCommandTestStub
    {
        return new VendorPublishCommandTestStub(new Filesystem);
    }
}

class VendorPublishCommandTestStub extends VendorPublishCommand
{
    public function searchChoices(array $choices, string $search): array
    {
        return $this->searchPublishableChoices($choices, $search);
    }
}
