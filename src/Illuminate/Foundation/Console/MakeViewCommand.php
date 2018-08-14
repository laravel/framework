<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;

class MakeViewCommand extends Command
{

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'make:view {view : The view name} 
                                      {layout? : Layout for view extend} 
                                      {section? : Section for insert content}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new view blade file';

    private $path = null;

    /**
     * Create a new view blade file.
     * @return void
     */
    public function __construct()
    {
        $this->path = resource_path();
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $arguments = $this->argument();
        $layout = $arguments['layout'];
        $section = $arguments['section'];
        $path = $this->makePath($arguments['view']);
        $content = $this->getContent($layout, $section);
        $this->createView($path, $content);
    }

    /**
     * Make the path for view file.
     * @param string $view
     * @return string
     */
    private function makePath($view)
    {
        $paths = explode('.', $view);
        $view = end($paths);
        $path = '';
        $count = count($paths) - 1;
        for ($i = 0; $i < $count; $i++) {
            $path .= $paths[$i].'/';
            if (! is_dir($this->path.'/views/'.$path)) {
                mkdir($this->path.'/views/'.$path);
            }
        }

        return $this->path.'/views/'.$path.$view;
    }

    /**
     * Provides a content for view file.
     * @param string $layout
     * @param string $section
     * @return string
     */
    private function getContent($layout, $section)
    {
        $content = '';
        if ($layout) {
            $content .= "@extends('{$layout}')\n\n";
        }
        if ($section) {
            $content .= "@section('{$section}') \n\n\n\n\n@endsection";
        }

        return $content;
    }

    /**
     * Put content on view file on choiced path.
     * @param string $path
     * @param string $content
     */
    private function createView($path, $content)
    {
        file_put_contents($path.'.blade.php', $content);
        $this->info('View created successfully.');
    }
}
