<?php

namespace Illuminate\Console;

use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Console\Application;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\ListCommand as SymfonyListCommand;
use Symfony\Component\Console\Command\Command;

class ListCommand extends SymfonyListCommand
{
    protected function configure()
    {
        parent::configure();
        
        $definition = $this->getDefinition();
        $definition->addOption(
            new InputOption('except-vendor', null, InputOption::VALUE_NONE, 'Do not include commands defined by vendor packages')
        );
        $definition->addOption(
            new InputOption('only-vendor', null, InputOption::VALUE_NONE, 'Only include commands defined by vendor packages')
        );

        $this->setDefinition($definition);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exceptVendor = $input->getOption('except-vendor');
        $onlyVendor = $input->getOption('only-vendor');

        if ($exceptVendor) {
            $this->getApplication()
                ->setShouldExcludeVendor(true)
                ->setShouldExcludeNonVendor(false);
        }

        if ($onlyVendor) {
            $this->getApplication()
                ->setShouldExcludeVendor(false)
                ->setShouldExcludeNonVendor(true);
        }

        return parent::execute($input, $output);
    }
}
