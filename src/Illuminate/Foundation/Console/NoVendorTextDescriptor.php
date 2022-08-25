<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionFunction;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Descriptor\ApplicationDescription;
use Symfony\Component\Console\Descriptor\TextDescriptor;

class NoVendorTextDescriptor extends TextDescriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeApplication(Application $application, array $options = [])
    {
        $description = new ApplicationDescription($application, $options['namespace'] ?? null);

        foreach ($description->getCommands() as $command) {
            if ($command instanceof ClosureCommand) {
                $fileName = (new ReflectionFunction($command->getCallback()))->getFileName();
            } else {
                $fileName = (new ReflectionClass($command))->getFileName();
            }

            if ($fileName && str_starts_with($fileName, base_path('vendor'))) {
                $command->setHidden(true);
            }
        }

        parent::describeApplication($application, $options);
    }
}
