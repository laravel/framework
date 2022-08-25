<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\DescriptorHelper;

class ListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'list
                    {namespace? : The namespace name}
                    {--format=txt : The output format (txt, xml, json, or md)}
                    {--raw : To output raw command list}
                    {--short : To skip describing commands\' arguments}
                    {--no-vendor : To skip commands defined by vendor dependencies}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List commands';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $helper = new DescriptorHelper();

        if($this->option('no-vendor')) {
            $helper->register('txt', new NoVendorTextDescriptor());
        }

        $helper->describe($this->output, $this->getApplication(), [
            'format' => $this->option('format'),
            'raw_text' => $this->option('raw'),
            'namespace' => $this->argument('namespace'),
            'short' => $this->option('short'),
        ]);
    }
}
