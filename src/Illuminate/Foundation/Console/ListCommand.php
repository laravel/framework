<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use ReflectionProperty;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\ListCommand as BaseListCommand;
use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends BaseListCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('list')
            ->setDefinition($this->createDefinition())
            ->setDescription('Lists commands')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> command lists all commands:

  <info>php %command.full_name%</info>

You can also display the commands for a specific namespace:

  <info>php %command.full_name% test</info>

You can also output the information in other formats by using the <comment>--format</comment> option:

  <info>php %command.full_name% --format=xml</info>

It's also possible to get raw list of commands (useful for embedding command runner):

  <info>php %command.full_name% --raw</info>

Finally you can display only the commands belonging to your application:

    <info>php %command.full_name% --app</info>
EOF
            );
    }

    /**
     * Retrieve the native definition.
     *
     * @return void
     */
    public function getNativeDefinition()
    {
        return $this->createDefinition();
    }

    /**
     * Execute the command.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface  $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = new DescriptorHelper();
        $app = $input->getOption('app') ? $this->getApplicationDomain() : $this->getApplication();

        $helper->describe($output, $app, [
            'format' => $input->getOption('format'),
            'raw_text' => $input->getOption('raw'),
            'namespace' => $input->getArgument('namespace'),
        ]);

        return 0;
    }

    /**
     * Retrieve the application with commands strictly belonging to it.
     *
     * @return \Symfony\Component\Console\Application
     */
    protected function getApplicationDomain()
    {
        $app = $this->getApplication();
        $domain = new Application($app->getName(), $app->getVersion());
        $commands = array_filter($app->all(), function ($command) {
            $reflection = new ReflectionClass($command);

            if ($command instanceof ClosureCommand) {
                $callback = new ReflectionProperty($command, 'callback');
                $callback->setAccessible(true);
                $reflection = new ReflectionFunction($callback->getValue($command));
            }

            return ! Str::startsWith($reflection->getFileName(), app()->basePath('vendor'));
        });

        return tap($domain)->addCommands($commands);
    }

    /**
     * Create the command definition.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function createDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('namespace', InputArgument::OPTIONAL, 'The namespace name'),
            new InputOption('raw', null, InputOption::VALUE_NONE, 'To output raw command list'),
            new InputOption('format', null, InputOption::VALUE_REQUIRED, 'The output format (txt, xml, json, or md)', 'txt'),
            new InputOption('app', 'a', InputOption::VALUE_NONE, 'Whether to output application commands only'),
        ]);
    }
}
