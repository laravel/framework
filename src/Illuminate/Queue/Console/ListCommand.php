<?php

namespace Illuminate\Queue\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Queue\Attributes\Queue as QueueAttribute;
use ReflectionClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Throwable;

use function Illuminate\Support\enum_value;

#[AsCommand(name: 'queue:list')]
class ListCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'queue:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "List the application's queue names";

    /**
     * Create a new queue list command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $queues = $this->queueNames();

        if ($queues === []) {
            $this->components->info('No queue names found.');

            return self::SUCCESS;
        }

        $this->newLine();

        foreach ($queues as $queue) {
            $this->output->writeln(sprintf(
                '  <fg=gray>⇂</> <fg=blue;options=bold>%s</>',
                OutputFormatter::escape($queue),
            ));
        }

        $this->newLine();
        $this->output->writeln(sprintf(
            '  <fg=blue;options=bold>Showing [%d] %s</>',
            count($queues),
            count($queues) === 1 ? 'queue' : 'queues',
        ));
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Get the application's known queue names.
     *
     * @return array<int, string>
     */
    protected function queueNames()
    {
        $names = array_merge(
            $this->configuredQueueNames(),
            $this->registeredQueueNames(),
            $this->discoveredQueueNames(),
        );

        $names = array_values(array_unique(array_filter(
            $names,
            fn ($name) => is_string($name) && $name !== '',
        )));

        natcasesort($names);

        $names = array_values($names);

        if (($default = array_search('default', $names, true)) !== false) {
            unset($names[$default]);

            array_unshift($names, 'default');
        }

        return array_values($names);
    }

    /**
     * Get queue names from the queue connection configuration.
     *
     * @return array<int, string>
     */
    protected function configuredQueueNames()
    {
        $names = [];

        foreach ($this->laravel['config']->get('queue.connections', []) as $connection) {
            $queue = enum_value($connection['queue'] ?? 'default');

            if (is_string($queue)) {
                array_push($names, ...$this->parseQueueList($queue));
            }

            if (($connection['driver'] ?? null) !== 'cloud') {
                continue;
            }

            $managed = $connection['queues'] ?? [];

            foreach (array_is_list($managed) ? $managed : array_keys($managed) as $queue) {
                $queue = enum_value($queue);

                if (is_string($queue)) {
                    $names[] = $queue;
                }
            }
        }

        return $names;
    }

    /**
     * Get queue names from the registered queue routes.
     *
     * @return array<int, string>
     */
    protected function registeredQueueNames()
    {
        if (! $this->laravel->bound('queue.routes')) {
            return [];
        }

        $names = [];

        foreach ($this->laravel->make('queue.routes')->all() as $route) {
            $queue = is_array($route) ? ($route[1] ?? null) : $route;
            $queue = enum_value($queue);

            if (is_string($queue)) {
                $names[] = $queue;
            }
        }

        return $names;
    }

    /**
     * Discover queue names in application classes and source files.
     *
     * @return array<int, string>
     */
    protected function discoveredQueueNames()
    {
        if (! function_exists('token_get_all')) {
            return [];
        }

        $names = [];

        foreach ($this->sourceFiles() as $file) {
            $source = $this->files->get($file);

            array_push($names, ...$this->literalQueueNames($source));

            foreach ($this->classNames($source) as $class) {
                array_push($names, ...$this->reflectedQueueNames($class));
            }
        }

        return $names;
    }

    /**
     * Get PHP source files from the application and routes directories.
     *
     * @return array<int, string>
     */
    protected function sourceFiles()
    {
        $files = [];

        foreach ([$this->laravel->path(), $this->laravel->basePath('routes')] as $path) {
            if (! $this->files->isDirectory($path)) {
                continue;
            }

            foreach ($this->files->allFiles($path) as $file) {
                if ($file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Extract literal onQueue call arguments from PHP source.
     *
     * @param  string  $source
     * @return array<int, string>
     */
    protected function literalQueueNames($source)
    {
        $tokens = token_get_all($source);
        $names = [];

        foreach ($tokens as $index => $token) {
            if (! is_array($token) || ! in_array($token[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], true)) {
                continue;
            }

            $method = $this->nextMeaningfulToken($tokens, $index);

            if (! is_array($method[1] ?? null) || $method[1][0] !== T_STRING || strcasecmp($method[1][1], 'onQueue') !== 0) {
                continue;
            }

            $opening = $this->nextMeaningfulToken($tokens, $method[0]);

            if (($opening[1] ?? null) !== '(') {
                continue;
            }

            $argument = $this->nextMeaningfulToken($tokens, $opening[0]);

            if (is_array($argument[1] ?? null) && $argument[1][0] === T_STRING) {
                $colon = $this->nextMeaningfulToken($tokens, $argument[0]);

                if (strcasecmp($argument[1][1], 'queue') !== 0 || ($colon[1] ?? null) !== ':') {
                    continue;
                }

                $argument = $this->nextMeaningfulToken($tokens, $colon[0]);
            }

            if (! is_array($argument[1] ?? null) || $argument[1][0] !== T_CONSTANT_ENCAPSED_STRING) {
                continue;
            }

            $closing = $this->nextMeaningfulToken($tokens, $argument[0]);

            if (($closing[1] ?? null) === ',') {
                $closing = $this->nextMeaningfulToken($tokens, $closing[0]);
            }

            if (($closing[1] ?? null) !== ')') {
                continue;
            }

            if (($name = $this->parseStringLiteral($argument[1][1])) !== '') {
                $names[] = $name;
            }
        }

        return $names;
    }

    /**
     * Extract class names declared in PHP source.
     *
     * @param  string  $source
     * @return array<int, class-string>
     */
    protected function classNames($source)
    {
        $tokens = token_get_all($source);
        $namespace = '';
        $classes = [];

        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = '';

                for ($next = $index + 1; isset($tokens[$next]); $next++) {
                    if (in_array($tokens[$next], [';', '{'], true)) {
                        break;
                    }

                    if (is_array($tokens[$next]) && in_array($tokens[$next][0], [T_STRING, T_NAME_QUALIFIED, T_NS_SEPARATOR], true)) {
                        $namespace .= $tokens[$next][1];
                    }
                }

                continue;
            }

            if (! in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
                continue;
            }

            $name = $this->nextMeaningfulToken($tokens, $index);

            if (is_array($name[1] ?? null) && $name[1][0] === T_STRING) {
                $classes[] = ltrim($namespace.'\\'.$name[1][1], '\\');
            }
        }

        return $classes;
    }

    /**
     * Get queue names declared by queueable class metadata.
     *
     * @param  class-string  $class
     * @return array<int, string>
     */
    protected function reflectedQueueNames($class)
    {
        try {
            $reflection = new ReflectionClass($class);
        } catch (Throwable) {
            return [];
        }

        if (! $reflection->isInstantiable() ||
            (! $reflection->implementsInterface(ShouldQueue::class) &&
            (! $reflection->implementsInterface(ShouldBroadcast::class) ||
             $reflection->implementsInterface(ShouldBroadcastNow::class)))) {
            return [];
        }

        $related = $reflection;

        do {
            $attributes = $related->getAttributes(QueueAttribute::class);

            foreach ($related->getTraits() as $trait) {
                array_push($attributes, ...$trait->getAttributes(QueueAttribute::class));
            }

            if ($attribute = $attributes[0] ?? null) {
                try {
                    $queue = enum_value($attribute->newInstance()->queue);
                } catch (Throwable) {
                    return [];
                }

                if ($reflection->hasProperty('queue')) {
                    $property = $reflection->getProperty('queue');

                    if ($property->isPublic() && ! $property->isStatic() &&
                        $property->getDeclaringClass()->isSubclassOf($related->getName())) {
                        $queue = enum_value($reflection->getDefaultProperties()['queue'] ?? null);
                    }
                }

                return is_string($queue) ? [$queue] : [];
            }
        } while ($related = $related->getParentClass());

        if (! $reflection->hasProperty('queue') ||
            ! $reflection->getProperty('queue')->isPublic() ||
            $reflection->getProperty('queue')->isStatic()) {
            return [];
        }

        $queue = enum_value($reflection->getDefaultProperties()['queue'] ?? null);

        return is_string($queue) ? [$queue] : [];
    }

    /**
     * Get the next token that is not whitespace or a comment.
     *
     * @param  array  $tokens
     * @param  int  $index
     * @return array{0: int, 1: mixed}|array{}
     */
    protected function nextMeaningfulToken(array $tokens, $index)
    {
        for ($index++; isset($tokens[$index]); $index++) {
            $token = $tokens[$index];

            if (is_array($token) && in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                continue;
            }

            return [$index, $token];
        }

        return [];
    }

    /**
     * Parse a PHP string literal without evaluating application code.
     *
     * @param  string  $literal
     * @return string
     */
    protected function parseStringLiteral($literal)
    {
        if (in_array($literal[0], ['b', 'B'], true)) {
            $literal = substr($literal, 1);
        }

        $quote = $literal[0];
        $value = substr($literal, 1, -1);

        if ($quote === "'") {
            return preg_replace('/\\\\([\\\\\'])/', '$1', $value);
        }

        $value = preg_replace_callback('/\\\\u\{([0-9a-fA-F]+)\}/', function ($match) {
            return mb_chr(hexdec($match[1]), 'UTF-8');
        }, $value);

        return preg_replace_callback('/\\\\(?:[nrtvef\\\\$"]|[0-7]{1,3}|x[0-9a-fA-F]{1,2})/', function ($match) {
            $escape = substr($match[0], 1);

            return match ($escape) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'v' => "\v",
                'e' => "\e",
                'f' => "\f",
                '\\' => '\\',
                '$' => '$',
                '"' => '"',
                default => str_starts_with($escape, 'x')
                    ? chr(hexdec(substr($escape, 1)))
                    : chr(octdec($escape)),
            };
        }, $value);
    }

    /**
     * Parse a comma-delimited queue list.
     *
     * @param  string  $queues
     * @return array<int, string>
     */
    protected function parseQueueList($queues)
    {
        return array_values(array_filter(
            array_map('trim', explode(',', $queues)),
            fn ($queue) => $queue !== '',
        ));
    }
}
