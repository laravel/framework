<?php

namespace Illuminate\Tests\Log;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\NormalizerFormatter;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\LogEntriesHandler;
use Monolog\Handler\NewRelicHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogHandler;
use Monolog\Logger as Monolog;
use Monolog\Processor\UidProcessor;
use Orchestra\Testbench\TestCase;
use ReflectionProperty;
use RuntimeException;

class LogManagerTest extends TestCase
{
    public function testLogManagerCachesLoggerInstances()
    {
        $manager = new LogManager($this->app);

        $logger1 = $manager->channel('single')->getLogger();
        $logger2 = $manager->channel('single')->getLogger();

        $this->assertSame($logger1, $logger2);
    }

    public function testLogManagerGetDefaultDriver()
    {
        $config = $this->app['config'];
        $config->set('logging.default', 'single');

        $manager = new LogManager($this->app);
        $this->assertEmpty($manager->getChannels());

        //we don't specify any channel name
        $manager->channel();
        $this->assertCount(1, $manager->getChannels());
        $this->assertEquals('single', $manager->getDefaultDriver());
    }

    public function testStackChannel()
    {
        $config = $this->app['config'];

        $config->set('logging.channels.stack', [
            'driver' => 'stack',
            'channels' => ['stderr', 'stdout'],
        ]);

        $config->set('logging.channels.stderr', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'notice',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $config->set('logging.channels.stdout', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'info',
            'with' => [
                'stream' => 'php://stdout',
                'bubble' => true,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('stack');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertInstanceOf(StreamHandler::class, $handlers[1]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertEquals(Monolog::INFO, $handlers[1]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());
        $this->assertTrue($handlers[1]->getBubble());
    }

    public function testLogManagerCreatesConfiguredMonologHandler()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.nonbubblingstream', [
            'driver' => 'monolog',
            'name' => 'foobar',
            'handler' => StreamHandler::class,
            'level' => 'notice',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('nonbubblingstream');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame('foobar', $logger->getName());
        $this->assertCount(1, $handlers);
        $this->assertInstanceOf(StreamHandler::class, $handlers[0]);
        $this->assertEquals(Monolog::NOTICE, $handlers[0]->getLevel());
        $this->assertFalse($handlers[0]->getBubble());

        $url = new ReflectionProperty(get_class($handlers[0]), 'url');
        $url->setAccessible(true);
        $this->assertSame('php://stderr', $url->getValue($handlers[0]));

        $config->set('logging.channels.logentries', [
            'driver' => 'monolog',
            'name' => 'le',
            'handler' => LogEntriesHandler::class,
            'with' => [
                'token' => '123456789',
            ],
        ]);

        $logger = $manager->channel('logentries');
        $handlers = $logger->getLogger()->getHandlers();

        $logToken = new ReflectionProperty(get_class($handlers[0]), 'logToken');
        $logToken->setAccessible(true);

        $this->assertInstanceOf(LogEntriesHandler::class, $handlers[0]);
        $this->assertSame('123456789', $logToken->getValue($handlers[0]));
    }

    public function testLogManagerCreatesMonologHandlerWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.newrelic', [
            'driver' => 'monolog',
            'name' => 'nr',
            'handler' => NewRelicHandler::class,
            'formatter' => 'default',
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('newrelic');
        $handler = $logger->getLogger()->getHandlers()[0];

        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(NormalizerFormatter::class, $handler->getFormatter());

        $config->set('logging.channels.newrelic2', [
            'driver' => 'monolog',
            'name' => 'nr',
            'handler' => NewRelicHandler::class,
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('newrelic2');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(NewRelicHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertSame('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreatesMonologHandlerWithProperFormatter()
    {
        $config = $this->app->make('config');
        $config->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
            'formatter' => HtmlFormatter::class,
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('null');
        $handler = $logger->getLogger()->getHandlers()[0];

        if (Monolog::API === 1) {
            $this->assertInstanceOf(NullHandler::class, $handler);
            $this->assertInstanceOf(HtmlFormatter::class, $handler->getFormatter());
        } else {
            $this->assertInstanceOf(NullHandler::class, $handler);
        }

        $config->set('logging.channels.null2', [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]);

        $logger = $manager->channel('null2');
        $handler = $logger->getLogger()->getHandlers()[0];

        if (Monolog::API === 1) {
            $this->assertInstanceOf(NullHandler::class, $handler);
            $this->assertInstanceOf(LineFormatter::class, $handler->getFormatter());
        } else {
            $this->assertInstanceOf(NullHandler::class, $handler);
        }
    }

    public function testItUtilisesTheNullDriverDuringTestsWhenNullDriverUsed()
    {
        $config = $this->app->make('config');
        $config->set('logging.default', null);
        $config->set('logging.channels.null', [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ]);
        $manager = new class($this->app) extends LogManager
        {
            protected function createEmergencyLogger()
            {
                throw new RuntimeException('Emergency logger was created.');
            }
        };

        // In tests, this should not need to create the emergency logger...
        $manager->info('message');

        // we should also be able to forget the null channel...
        $this->assertCount(1, $manager->getChannels());
        $manager->forgetChannel();
        $this->assertCount(0, $manager->getChannels());

        // However in production we want it to fallback to the emergency logger...
        $this->app['env'] = 'production';
        try {
            $manager->info('message');

            $this->fail('Emergency logger was not created as expected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Emergency logger was created.', $exception->getMessage());
        }
    }

    public function testLogManagerCreateSingleDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultsingle', [
            'driver' => 'single',
            'name' => 'ds',
            'path' => storage_path('logs/laravel.log'),
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsingle');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formattedsingle', [
            'driver' => 'single',
            'name' => 'fs',
            'path' => storage_path('logs/laravel.log'),
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formattedsingle');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertSame('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreateDailyDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultdaily', [
            'driver' => 'daily',
            'name' => 'dd',
            'path' => storage_path('logs/laravel.log'),
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultdaily');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formatteddaily', [
            'driver' => 'daily',
            'name' => 'fd',
            'path' => storage_path('logs/laravel.log'),
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formatteddaily');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertSame('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerCreateSyslogDriverWithConfiguredFormatter()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.defaultsyslog', [
            'driver' => 'syslog',
            'name' => 'ds',
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('defaultsyslog');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $config->set('logging.channels.formattedsyslog', [
            'driver' => 'syslog',
            'name' => 'fs',
            'formatter' => HtmlFormatter::class,
            'formatter_with' => [
                'dateFormat' => 'Y/m/d--test',
            ],
        ]);

        $logger = $manager->channel('formattedsyslog');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(SyslogHandler::class, $handler);
        $this->assertInstanceOf(HtmlFormatter::class, $formatter);

        $dateFormat = new ReflectionProperty(get_class($formatter), 'dateFormat');
        $dateFormat->setAccessible(true);

        $this->assertSame('Y/m/d--test', $dateFormat->getValue($formatter));
    }

    public function testLogManagerPurgeResolvedChannels()
    {
        $manager = new LogManager($this->app);

        $this->assertEmpty($manager->getChannels());

        $manager->channel('single')->getLogger();

        $this->assertCount(1, $manager->getChannels());

        $manager->forgetChannel('single');

        $this->assertEmpty($manager->getChannels());
    }

    public function testLogManagerCanBuildOnDemandChannel()
    {
        $manager = new LogManager($this->app);

        $logger = $manager->build([
            'driver' => 'single',
            'path' => storage_path('logs/on-demand.log'),
        ]);
        $handler = $logger->getLogger()->getHandlers()[0];

        $this->assertInstanceOf(StreamHandler::class, $handler);

        $url = new ReflectionProperty(get_class($handler), 'url');
        $url->setAccessible(true);

        $this->assertSame(storage_path('logs/on-demand.log'), $url->getValue($handler));
    }

    public function testLogManagerCanUseOnDemandChannelInOnDemandStack()
    {
        $manager = new LogManager($this->app);
        $this->app['config']->set('logging.channels.test', [
            'driver' => 'single',
        ]);

        $factory = new class()
        {
            public function __invoke()
            {
                return new Monolog(
                    'uuid',
                    [new StreamHandler(storage_path('logs/custom.log'))],
                    [new UidProcessor()]
                );
            }
        };
        $channel = $manager->build([
            'driver' => 'custom',
            'via' => get_class($factory),
        ]);
        $logger = $manager->stack(['test', $channel]);

        $handler = $logger->getLogger()->getHandlers()[1];
        $processor = $logger->getLogger()->getProcessors()[0];

        $this->assertInstanceOf(StreamHandler::class, $handler);
        $this->assertInstanceOf(UidProcessor::class, $processor);

        $url = new ReflectionProperty(get_class($handler), 'url');
        $url->setAccessible(true);

        $this->assertSame(storage_path('logs/custom.log'), $url->getValue($handler));
    }

    public function testWrappingHandlerInFingersCrossedWhenActionLevelIsUsed()
    {
        $config = $this->app['config'];

        $config->set('logging.channels.fingerscrossed', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'debug',
            'action_level' => 'critical',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('fingerscrossed');
        $handlers = $logger->getLogger()->getHandlers();

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertCount(1, $handlers);

        $expectedFingersCrossedHandler = $handlers[0];
        $this->assertInstanceOf(FingersCrossedHandler::class, $expectedFingersCrossedHandler);

        $activationStrategyProp = new ReflectionProperty(get_class($expectedFingersCrossedHandler), 'activationStrategy');
        $activationStrategyProp->setAccessible(true);
        $activationStrategyValue = $activationStrategyProp->getValue($expectedFingersCrossedHandler);

        $actionLevelProp = new ReflectionProperty(get_class($activationStrategyValue), 'actionLevel');
        $actionLevelProp->setAccessible(true);
        $actionLevelValue = $actionLevelProp->getValue($activationStrategyValue);

        $this->assertEquals(Monolog::CRITICAL, $actionLevelValue);

        if (method_exists($expectedFingersCrossedHandler, 'getHandler')) {
            $expectedStreamHandler = $expectedFingersCrossedHandler->getHandler();
        } else {
            $handlerProp = new ReflectionProperty(get_class($expectedFingersCrossedHandler), 'handler');
            $handlerProp->setAccessible(true);
            $expectedStreamHandler = $handlerProp->getValue($expectedFingersCrossedHandler);
        }
        $this->assertInstanceOf(StreamHandler::class, $expectedStreamHandler);
        $this->assertEquals(Monolog::DEBUG, $expectedStreamHandler->getLevel());
    }

    public function testFingersCrossedHandlerStopsRecordBufferingAfterFirstFlushByDefault()
    {
        $config = $this->app['config'];

        $config->set('logging.channels.fingerscrossed', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'debug',
            'action_level' => 'critical',
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('fingerscrossed');
        $handlers = $logger->getLogger()->getHandlers();

        $expectedFingersCrossedHandler = $handlers[0];

        $stopBufferingProp = new ReflectionProperty(get_class($expectedFingersCrossedHandler), 'stopBuffering');
        $stopBufferingProp->setAccessible(true);
        $stopBufferingValue = $stopBufferingProp->getValue($expectedFingersCrossedHandler);

        $this->assertTrue($stopBufferingValue);
    }

    public function testFingersCrossedHandlerCanBeConfiguredToResumeBufferingAfterFlushing()
    {
        $config = $this->app['config'];

        $config->set('logging.channels.fingerscrossed', [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'level' => 'debug',
            'action_level' => 'critical',
            'stop_buffering' => false,
            'with' => [
                'stream' => 'php://stderr',
                'bubble' => false,
            ],
        ]);

        $manager = new LogManager($this->app);

        // create logger with handler specified from configuration
        $logger = $manager->channel('fingerscrossed');
        $handlers = $logger->getLogger()->getHandlers();

        $expectedFingersCrossedHandler = $handlers[0];

        $stopBufferingProp = new ReflectionProperty(get_class($expectedFingersCrossedHandler), 'stopBuffering');
        $stopBufferingProp->setAccessible(true);
        $stopBufferingValue = $stopBufferingProp->getValue($expectedFingersCrossedHandler);

        $this->assertFalse($stopBufferingValue);
    }

    public function testItSharesContextWithAlreadyResolvedChannels()
    {
        $manager = new LogManager($this->app);
        $channel = $manager->channel('single');
        $context = null;

        $channel->listen(function ($message) use (&$context) {
            $context = $message->context;
        });
        $manager->shareContext([
            'invocation-id' => 'expected-id',
        ]);
        $channel->info('xxxx');

        $this->assertSame(['invocation-id' => 'expected-id'], $context);
    }

    public function testItSharesContextWithFreshlyResolvedChannels()
    {
        $manager = new LogManager($this->app);
        $context = null;

        $manager->shareContext([
            'invocation-id' => 'expected-id',
        ]);
        $manager->channel('single')->listen(function ($message) use (&$context) {
            $context = $message->context;
        });
        $manager->channel('single')->info('xxxx');

        $this->assertSame(['invocation-id' => 'expected-id'], $context);
    }

    public function testContextCanBePublicallyAccessedByOtherLoggingSystems()
    {
        $manager = new LogManager($this->app);
        $context = null;

        $manager->shareContext([
            'invocation-id' => 'expected-id',
        ]);

        $this->assertSame($manager->sharedContext(), ['invocation-id' => 'expected-id']);
    }

    public function testItSharesContextWithStacksWhenTheyAreResolved()
    {
        $manager = new LogManager($this->app);
        $context = null;

        $manager->shareContext([
            'invocation-id' => 'expected-id',
        ]);
        $stack = $manager->stack(['single']);
        $stack->listen(function ($message) use (&$context) {
            $context = $message->context;
        });
        $stack->info('xxxx');

        $this->assertSame(['invocation-id' => 'expected-id'], $context);
    }

    public function testItMergesSharedContextRatherThanReplacing()
    {
        $manager = new LogManager($this->app);
        $context = null;

        $manager->shareContext([
            'invocation-id' => 'expected-id',
        ]);
        $manager->shareContext([
            'invocation-start' => 1651800456,
        ]);
        $manager->channel('single')->listen(function ($message) use (&$context) {
            $context = $message->context;
        });
        $manager->channel('single')->info('xxxx', [
            'logged' => 'context',
        ]);

        $this->assertSame([
            'invocation-id' => 'expected-id',
            'invocation-start' => 1651800456,
            'logged' => 'context',
        ], $context);
        $this->assertSame([
            'invocation-id' => 'expected-id',
            'invocation-start' => 1651800456,
        ], $manager->sharedContext());
    }

    public function testFlushSharedContext()
    {
        $manager = new LogManager($this->app);

        $manager->shareContext($context = ['foo' => 'bar']);

        $this->assertSame($context, $manager->sharedContext());

        $manager->flushSharedContext();

        $this->assertEmpty($manager->sharedContext());
    }

    public function testLogManagerCreateCustomFormatterWithTap()
    {
        $config = $this->app['config'];
        $config->set('logging.channels.custom', [
            'driver' => 'single',
            'tap' => [CustomizeFormatter::class],
        ]);

        $manager = new LogManager($this->app);

        $logger = $manager->channel('custom');
        $handler = $logger->getLogger()->getHandlers()[0];
        $formatter = $handler->getFormatter();

        $this->assertInstanceOf(LineFormatter::class, $formatter);

        $format = new ReflectionProperty(get_class($formatter), 'format');
        $format->setAccessible(true);

        $this->assertEquals(
            '[%datetime%] %channel%.%level_name%: %message% %context% %extra%',
            rtrim($format->getValue($formatter)));
    }
}

class CustomizeFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                '[%datetime%] %channel%.%level_name%: %message% %context% %extra%'
            ));
        }
    }
}
