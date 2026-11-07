<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\PruneBatchesCommand;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class PruneBatchesCommandTest extends TestCase
{
    use VerifiesDoubles;

    public function testAllowPruningAllUnfinishedBatches()
    {
        $container = new Application;
        $repo = Double::for(DatabaseBatchRepository::class);
        $container->instance(BatchRepository::class, $repo);

        $command = new PruneBatchesCommand;
        $command->setLaravel($container);

        $command->run(new ArrayInput(['--unfinished' => 0]), new NullOutput());

        $repo->received('pruneUnfinished')->times(1);
    }

    public function testAllowPruningAllCancelledBatches()
    {
        $container = new Application;
        $repo = Double::for(DatabaseBatchRepository::class);
        $container->instance(BatchRepository::class, $repo);

        $command = new PruneBatchesCommand;
        $command->setLaravel($container);

        $command->run(new ArrayInput(['--cancelled' => 0]), new NullOutput());

        $repo->received('pruneCancelled')->times(1);
    }
}
