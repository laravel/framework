<?php

namespace Illuminate\Foundation\Console;

use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

use Illuminate\Console\Command;

class CpanelCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cpanel:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a ZIP file containing your Laravel
                    application in a directory structure that is safe
                    to upload to traditional shared cPanel hosting.
                    The index.php file is also modified to account
                    for this structure change.';

    /**
     * Recursively copies directories and their contents to a new location
     *
     * Credit to: "gimmicklessgpt at gmail dot com"
     *            (http://php.net/manual/en/function.copy.php#91010)
     *
     * @param  string  $src  The directory to copy
     * @param  string  $dst  The destination to copy the directory to
     *
     * @return void
     */
    private function copyDirectory($src, $dst)
    {
        // Open the source folder
        $dir = opendir($src);

        // Create the destination folder if it doesn't exist
        if(!file_exists($dst)) {
            mkdir($dst);
        }

        // Loop through list of files in directory
        while($file = readdir($dir)) {

            // If the file/dir is real, then continue
            if(($file != '.') && ($file != '..')) {

                // If the $file is a directory, recursively call this function
                if(is_dir($src . '/' . $file)) {
                    $this->copyDirectory($src . '/' . $file, $dst . '/' . $file);
                }
                // Otherwise copy the file over
                else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        // Finally close the directory
        closedir($dir);
    }

    /**
     * Zips an entire directory and its contents.
     *
     * Credit to: 'Dador' (https://stackoverflow.com/users/596207/dador)
     * SO Thread: https://stackoverflow.com/a/4914807/3011431
     *
     * @param  string  $tempDir  The temporary directory we copied everything to
     *
     * @return void
     */
    private function zipFolder($tempDir)
    {
        // Get real path for our folder
        $rootPath = $tempDir;

        // Initialize archive object
        $zip = new ZipArchive();
        $zipName = 'cpanel_' . str_random(6) . '.zip';
        $zip->open($zipName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        // Zip archive will be created only after closing object
        $zip->close();

        // Hand the name of the zip back so we can tell the user
        return $zipName;
    }

    /**
     * Recursively deletes a directory and its contents
     *
     * Credit to: https://paulund.co.uk/php-delete-directory-and-files-in-directory
     *
     * @param  string  $dirname  The directory to delete
     *
     * @return void
     */
    private function deleteDirectory($dirname)
    {
        $dir_handle = null;

        // If the file is a directory
        if(is_dir($dirname)) {
            // Grab the handle for the directory
            $dir_handle = opendir($dirname);
        }

        // If the handle was invalid don't continue
        if($dir_handle == null || $dir_handle == false) {
            return;
        }

        // Loop through all files and directories in a directory
        while($file = readdir($dir_handle)) {
            // Ignore '.' and '..'
            if ($file != "." && $file != "..") {
                // If it's a file, delete it
                if (!is_dir($dirname."/".$file)) {
                    unlink($dirname."/".$file);
                }
                // Otherwise, recurse
                else {
                    $this->deleteDirectory($dirname.'/'.$file);
                }
            }
        }
        // Close the handle
        closedir($dir_handle);
        // Finally remove the directory when all recursion is completed
        rmdir($dirname);
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        // Get a random name for our temp directory
        $tmp = 'tmp_' . str_random(20);
        $tmpPath = base_path($tmp);

        // There's no way this already exists, right?
        if(file_exists($tmpPath)) {
            $this->error('Could not create temp directory, please try again.');
            return;
        }

        // The name of the directory our Laravel app will live in
        $appDirName = snake_case(env('APP_NAME') . '_laravel');
        $appDir = $tmpPath . '/' . $appDirName;

        // The name of the public dir in our cPanel (default 'public_html')
        $pubDir = $tmpPath . '/public_html';

        // Make the temp directory + new structure
        $mkdirSuccess = mkdir($tmpPath) &&
                        mkdir($appDir) &&
                        mkdir($pubDir);

        // If making the directory failed
        if(!$mkdirSuccess) {
            $this->error('Failed to create temp directory.
                          Please check you have the correct
                          permissions and try again.');
            return;
        }

        // Get the contents of our project
        $ourProject = scandir(base_path());

        // Loop through our project and copy everything to the temp folder
        foreach($ourProject as $file) {
            // Ignore '.', '..', '.git', the temp dir and some others
            if( $file == '.' ||
                $file == '..' ||
                $file == $tmp ||
                $file == '.git' ||
                $file == '.gitignore' ||
                $file == '.gitkeep' ||
                $file == '.gitattributes') {
                continue;
            }

            // Print the progress and some extra info
            $this->info('Copying ' . base_path($file));

            // If the '$file' is a directory, use our recursive copy function
            if(is_dir(base_path($file))) {
                // If it's the 'public' directory, place it in the new structure
                if($file == 'public') {
                    $this->copyDirectory(base_path($file), $pubDir);
                }
                // Otherwise continue normally
                else {
                    $this->copyDirectory(base_path($file), $appDir . '/' . $file);
                }
            }
            // Otherwise just copy the file
            else {
                copy(base_path($file), $appDir . '/' . $file);
            }
        }

        // Helpful output
        $this->info('Copying complete!');

        // Edit the copied public/index.php file so the app works correctly
        // with the new structure.
        $indexPhp = file_get_contents($pubDir . '/index.php');

        // Make relative paths correct
        $indexPhp = str_replace(
            [
                // Autoload path
                "require __DIR__.'/../vendor/autoload.php';",

                // App path
                "\$app = require_once __DIR__.'/../bootstrap/app.php';",
            ],
            [
                // Autoload path
                "require __DIR__.'/../$appDirName/vendor/autoload.php';",

                // App path, plus additional code to bind new public path
                "\$app = require_once __DIR__.'/../$appDirName/bootstrap/app.php';\r\n" .
                "\r\n" .
                "\$app->bind('path.public', function() {\r\n" .
                "\treturn __DIR__;\r\n" .
                "});"

            ],
            $indexPhp
        );

        // More helpful output
        $this->info('Files edited!');

        // Write the edited index.php back to the copied version
        file_put_contents($pubDir . '/index.php', $indexPhp);

        // Zip the entire folder
        $zipName = $this->zipFolder($tmpPath);

        // More helpful output
        $this->info('Files zipped!');

        // Remove the temp folder
        $this->deleteDirectory($tmpPath);

        // Final helpful output
        $this->info('Temp directory removed!');
        $this->info('cPanel-safe Laravel app generated @ ' . $zipName);
    }
}
