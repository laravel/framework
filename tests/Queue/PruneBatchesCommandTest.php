<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Bus\BatchRepository;
use Illuminate\Bus\DatabaseBatchRepository;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\PruneBatchesCommand;
use Mockery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class PruneBatchesCommandTest extends TestCase
{
    public function testAllowPruningAllUnfinishedBatches()
    {
        $container = new Application;
        $repo = Mockery::spy(DatabaseBatchRepository::class);
        $container->instance(BatchRepository::class, $repo);

        $command = new PruneBatchesCommand;
        $command->setLaravel($container);

        $command->run(new ArrayInput(['--unfinished' => 0]), new NullOutput());

        $repo->shouldHaveReceived('pruneUnfinished')->once();
    }

    public function testAllowPruningAllCancelledBatches()
    {
        $container = new Application;
        $repo = Mockery::spy(DatabaseBatchRepository::class);
        $container->instance(BatchRepository::class, $repo);

        $command = new PruneBatchesCommand;
        $command->setLaravel($container);

        $command->run(new ArrayInput(['--cancelled' => 0]), new NullOutput());

        $repo->shouldHaveReceived('pruneCancelled')->once();
    }
}
