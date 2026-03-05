<?php

namespace Illuminate\Database\Console\Seeds;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Console\Prohibitable;
use Illuminate\Database\ConnectionResolverInterface as Resolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use InvalidArgumentException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'db:seed')]
class SeedCommand extends Command
{
    use ConfirmableTrait, Prohibitable;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'db:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed the database with records';

    /**
     * The connection resolver instance.
     *
     * @var \Illuminate\Database\ConnectionResolverInterface
     */
    protected $resolver;

    /**
     * Create a new database seed command instance.
     *
     * @param  \Illuminate\Database\ConnectionResolverInterface  $resolver
     */
    public function __construct(Resolver $resolver)
    {
        parent::__construct();

        $this->resolver = $resolver;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->isProhibited() ||
            ! $this->confirmToProceed()) {
            return Command::FAILURE;
        }

        $this->components->info('Seeding database.');

        $previousConnection = $this->resolver->getDefaultConnection();

        $this->resolver->setDefaultConnection($this->getDatabase());

        $seeder = $this->getSeeder();

        $parameters = $this->resolveSeederParameters($seeder, $this->parseParameters());

        Model::unguarded(function () use ($seeder, $parameters) {
            $seeder->__invoke($parameters);
        });

        if ($previousConnection) {
            $this->resolver->setDefaultConnection($previousConnection);
        }

        return 0;
    }

    /**
     * Parse the parameters passed to the command using the --with option.
     *
     * @return array<string, string>
     */
    protected function parseParameters(): array
    {
        $parameters = [];

        foreach ($this->option('with') ?? [] as $parameter) {
            if (! is_string($parameter) || ! str_contains($parameter, '=')) {
                throw new InvalidArgumentException('The --with option expects values in key=value format.');
            }

            [$key, $value] = explode('=', $parameter, 2);

            $key = trim($key);

            if ($key === '') {
                throw new InvalidArgumentException('The --with option expects non-empty keys.');
            }

            $parameters[$key] = trim($value);
        }

        return $parameters;
    }

    /**
     * Resolve and cast parameters for the given seeder run method.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @param  array<string, string>  $parameters
     * @return array<string, mixed>
     */
    protected function resolveSeederParameters(Seeder $seeder, array $parameters): array
    {
        if ($parameters === []) {
            return [];
        }

        $method = new ReflectionMethod($seeder, 'run');
        $signature = [];

        foreach ($method->getParameters() as $parameter) {
            $signature[$parameter->getName()] = $parameter;
        }

        foreach ($parameters as $name => $value) {
            if (! array_key_exists($name, $signature)) {
                throw new InvalidArgumentException("Unknown seeder parameter [{$name}] for [".get_class($seeder).'].');
            }

            $parameters[$name] = $this->castSeederParameterValue($seeder, $signature[$name], $value);
        }

        return $parameters;
    }

    /**
     * Cast a seeder parameter value based on the run method signature.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @param  \ReflectionParameter  $parameter
     * @param  string  $value
     * @return mixed
     */
    protected function castSeederParameterValue(Seeder $seeder, ReflectionParameter $parameter, string $value)
    {
        $type = $parameter->getType();

        if ($type === null) {
            return $value;
        }

        if (! $type instanceof ReflectionNamedType || ! $type->isBuiltin()) {
            throw new InvalidArgumentException(
                "Unable to pass [{$parameter->getName()}] to [".get_class($seeder).'] via --with because only single built-in scalar parameter types are supported.'
            );
        }

        return match ($type->getName()) {
            'int' => $this->castIntegerSeederParameter($seeder, $parameter, $value),
            'float' => $this->castFloatSeederParameter($seeder, $parameter, $value),
            'bool' => $this->castBooleanSeederParameter($seeder, $parameter, $value),
            'string' => $value,
            'array' => [$value],
            default => $value,
        };
    }

    /**
     * Cast a seeder parameter value to integer.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @param  \ReflectionParameter  $parameter
     * @param  string  $value
     * @return int
     */
    protected function castIntegerSeederParameter(Seeder $seeder, ReflectionParameter $parameter, string $value): int
    {
        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        if ($parsed === false) {
            throw new InvalidArgumentException(
                "The [{$parameter->getName()}] parameter for [".get_class($seeder).'] must be a valid integer.'
            );
        }

        return (int) $parsed;
    }

    /**
     * Cast a seeder parameter value to float.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @param  \ReflectionParameter  $parameter
     * @param  string  $value
     * @return float
     */
    protected function castFloatSeederParameter(Seeder $seeder, ReflectionParameter $parameter, string $value): float
    {
        $parsed = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($parsed === false) {
            throw new InvalidArgumentException(
                "The [{$parameter->getName()}] parameter for [".get_class($seeder).'] must be a valid float.'
            );
        }

        return (float) $parsed;
    }

    /**
     * Cast a seeder parameter value to boolean.
     *
     * @param  \Illuminate\Database\Seeder  $seeder
     * @param  \ReflectionParameter  $parameter
     * @param  string  $value
     * @return bool
     */
    protected function castBooleanSeederParameter(Seeder $seeder, ReflectionParameter $parameter, string $value): bool
    {
        if ($value === '') {
            throw new InvalidArgumentException(
                "The [{$parameter->getName()}] parameter for [".get_class($seeder).'] must be a valid boolean.'
            );
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($parsed === null) {
            throw new InvalidArgumentException(
                "The [{$parameter->getName()}] parameter for [".get_class($seeder).'] must be a valid boolean.'
            );
        }

        return $parsed;
    }

    /**
     * Get a seeder instance from the container.
     *
     * @return \Illuminate\Database\Seeder
     */
    protected function getSeeder()
    {
        $class = $this->input->getArgument('class') ?? $this->input->getOption('class');

        if (! str_contains($class, '\\')) {
            $class = 'Database\\Seeders\\'.$class;
        }

        if ($class === 'Database\\Seeders\\DatabaseSeeder' &&
            ! class_exists($class)) {
            $class = 'DatabaseSeeder';
        }

        return $this->laravel->make($class)
            ->setContainer($this->laravel)
            ->setCommand($this);
    }

    /**
     * Get the name of the database connection to use.
     *
     * @return string
     */
    protected function getDatabase()
    {
        $database = $this->input->getOption('database');

        return $database ?: $this->laravel['config']['database.default'];
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['class', InputArgument::OPTIONAL, 'The class name of the root seeder', null],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['class', null, InputOption::VALUE_OPTIONAL, 'The class name of the root seeder', 'Database\\Seeders\\DatabaseSeeder'],
            ['database', null, InputOption::VALUE_OPTIONAL, 'The database connection to seed'],
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production'],
            ['with', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Values passed into the seeder'],
        ];
    }
}
