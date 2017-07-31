<?php

namespace Illuminate\Translation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Filesystem\Filesystem;

class LangPublishCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:publish
                    {--locale= : Will publish only a specific locale}
                    {--force : Overwrite existing language files by default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish the default lang files into other languages.';

    /**
     * Every languages that can be published (have a folder with files associated with)
     *
     * @var array
     */
    protected $langs = [
        'fr_CA',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createLanguagesDirectories();
        
        $this->exportLanguagesFiles();

        $this->info('Language files exported successfully to your lang folder.');
    }

    /**
     * Create the directories for the language files.
     *
     * @return void
     */
    protected function createLanguagesDirectories()
    {
        if (is_null($this->option('locale'))) {
            foreach ($this->langs as $lang) {
                $this->createLanguageDirectory($lang);
            }
        }

        if (! is_null($this->option('locale'))) {
            $this->createLanguageDirectory($this->option('locale'));
        }
    }

    /**
     * Create a single directory for a language file.
     * @param  string $lang The language code to create a directory for
     * @return void
     */
    protected function createLanguageDirectory($lang)
    {
        if (! is_dir(resource_path('lang/' . $lang))) {
            mkdir(resource_path('lang/'. $lang), 0755, true);
        }
    }

    /**
     * Export the language files.
     *
     * @return void
     */
    protected function exportLanguagesFiles()
    {
        
        if (is_null($this->option('locale'))) {
            foreach ($this->langs as $lang) {
                $this->copyLanguageFolderContent($lang);
            }
        }

        if (! is_null($this->option('locale'))) {
            $this->copyLanguageFolderContent($this->option('locale'));
        }
    }

    /**
     * Recursively copy the content of a language directory to the resource/lang folder.
     * @param   string $lang The language code to copy the files from
     * @return void
     */
    protected function copyLanguageFolderContent($lang)
    {
        $sourceFolder = __DIR__.'/resources/lang/'.$lang;
        $targerFolder = resource_path('lang/' . $lang);

        foreach ($iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceFolder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        ) as $item) {
            if ($item->isDir()) {
                mkdir($targerFolder . DIRECTORY_SEPARATOR . $iterator->getSubPathName(), 0755, true);
            } else {
                if (file_exists($item) && ! $this->option('force')) {
                    if (! $this->confirm("The [{$item}] file already exists. Do you want to replace it?")) {
                            continue;
                    }
                }
                copy($item, $targerFolder . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
    }
}
