<?php

namespace Illuminate\Tests\Queue;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Console\ListCommand;
use Illuminate\Queue\QueueRoutes;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class QueueListCommandTest extends TestCase
{
    protected Filesystem $files;

    protected string $basePath;

    protected \Closure $autoload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = new Filesystem;
        $this->basePath = sys_get_temp_dir().'/laravel-queue-list-'.bin2hex(random_bytes(8));

        $this->files->makeDirectory($this->basePath.'/app/Jobs', 0755, true);
        $this->files->makeDirectory($this->basePath.'/routes', 0755, true);

        $this->autoload = function ($class) {
            $namespace = __NAMESPACE__.'\\QueueListFixtures\\';

            if (! str_starts_with($class, $namespace)) {
                return;
            }

            $file = $this->basePath.'/app/Jobs/'.substr($class, strlen($namespace)).'.php';

            if ($this->files->exists($file)) {
                require $file;
            }
        };

        spl_autoload_register($this->autoload);
    }

    protected function tearDown(): void
    {
        spl_autoload_unregister($this->autoload);

        $this->files->deleteDirectory($this->basePath);

        parent::tearDown();
    }

    public function testItListsKnownQueueNames(): void
    {
        $this->files->put($this->basePath.'/app/Jobs/AttributeJob.php', <<<'PHP'
<?php

namespace Illuminate\Tests\Queue\QueueListFixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Queue;

#[Queue('attribute-queue')]
class AttributeJob implements ShouldQueue {}
PHP);

        $this->files->put($this->basePath.'/app/Jobs/PropertyJob.php', <<<'PHP'
<?php

namespace Illuminate\Tests\Queue\QueueListFixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

class PropertyJob implements ShouldQueue
{
    public $queue = 'property-queue';
}
PHP);

        $this->files->put($this->basePath.'/app/Jobs/NonQueuedClass.php', <<<'PHP'
<?php

namespace Illuminate\Tests\Queue\QueueListFixtures;

class NonQueuedClass
{
    public $queue = 'not-a-queue';
}
PHP);

        $this->files->put($this->basePath.'/app/Jobs/StaticPropertyJob.php', <<<'PHP'
<?php

namespace Illuminate\Tests\Queue\QueueListFixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

class StaticPropertyJob implements ShouldQueue
{
    public static $queue = 'static-queue';
}
PHP);

        $this->files->put($this->basePath.'/app/dispatches.php', <<<'PHP'
<?php

dispatch(new PropertyJob)->onQueue('channel-sync');
dispatch(new PropertyJob)->onQueue(queue: 'named-queue');
dispatch(new PropertyJob)->onQueue(b'binary-queue');
dispatch(new PropertyJob)->onQueue("unicode-\u{41}");
dispatch(new PropertyJob)->onQueue('<info>markup</info>');
dispatch(new PropertyJob)->onQueue($dynamicQueue);
PHP);

        $this->files->put($this->basePath.'/routes/console.php', <<<'PHP'
<?php

dispatch(new RouteJob)->onQueue('route-queue');
PHP);

        $app = new Application($this->basePath);
        $app->instance('config', new Repository([
            'queue' => [
                'connections' => [
                    'database' => ['queue' => 'default'],
                    'redis' => ['queue' => 'low, high'],
                    'sqs' => ['queue' => '0'],
                    'sync' => ['driver' => 'sync'],
                    'cloud' => [
                        'driver' => 'cloud',
                        'queues' => [
                            'managed-default' => ['timeout' => 10],
                            'managed-high' => ['timeout' => 1],
                        ],
                    ],
                ],
            ],
        ]));

        $routes = new QueueRoutes;
        $routes->set(QueueListFixtures\PropertyJob::class, 'routed,queue');
        $app->instance('queue.routes', $routes);

        $command = new ListCommand($this->files);
        $command->setLaravel($app);

        $output = new BufferedOutput;
        $exitCode = $command->run(new ArrayInput([]), $output);

        $this->assertSame(0, $exitCode);
        $this->assertSame([
            '⇂ default',
            '⇂ 0',
            '⇂ <info>markup</info>',
            '⇂ attribute-queue',
            '⇂ binary-queue',
            '⇂ channel-sync',
            '⇂ high',
            '⇂ low',
            '⇂ managed-default',
            '⇂ managed-high',
            '⇂ named-queue',
            '⇂ property-queue',
            '⇂ route-queue',
            '⇂ routed,queue',
            '⇂ unicode-A',
            '',
            'Showing [15] queues',
        ], array_map('trim', preg_split('/\R/', trim($output->fetch()))));
    }
}
