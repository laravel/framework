<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\ListCommand as BaseListCommand;

class ListCommand extends BaseListCommand
{
    /**
     * The OutputStyle instance.
     *
     * @var \Illuminate\Console\OutputStyle|null
     */
    private $output;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('format') === 'txt' && ! $input->getOption('raw')) {
            $this->output = new OutputStyle($input, $output);

            $this->describeTitle()
                ->describeUsage()
                ->describeCommands();
        } else {
            parent::execute($input, $output);
        }
    }

    /**
     * Describes the application title.
     *
     * @return $this
     */
    protected function describeTitle()
    {
        $application = $this->getApplication();

        $this->output->newLine();

        $this->output->write(
            "<fg=white;options=bold>{$application->getName()} </> <fg=green;options=bold>{$application->getVersion()}</>"
        );

        $this->output->newLine(2);

        return $this;
    }

    /**
     * Describes the application usage.
     *
     * @return $this
     */
    protected function describeUsage()
    {
        $binary = ARTISAN_BINARY;

        $this->output->write("<fg=yellow;options=bold>USAGE:</> $binary <command> [options] [arguments]");

        $this->output->newLine();

        return $this;
    }

    /**
     * Describes the application commands.
     *
     * @return $this
     */
    protected function describeCommands()
    {
        $width = 0;

        $namespaces = collect($this->getApplication()->all())->filter(function ($command) {
            return ! $command->isHidden();
        })->groupBy(function ($command) use (&$width) {
            $nameParts = explode(':', $name = $command->getName());
            $width = max($width, mb_strlen($name));

            return isset($nameParts[1]) ? $nameParts[0] : '';
        })->sortKeys()->each(function ($commands) use ($width) {
            $this->output->newLine();

            foreach ($commands as $command) {
                $this->output->write(sprintf(
                    '  <fg=green>%s</>%s%s',
                    $command->getName(),
                    str_repeat(' ', $width - mb_strlen($command->getName()) + 1),
                    $command->getDescription()
                ));

                $this->output->newLine();
            }
        });

        return $this;
    }
}
