<?php

namespace Illuminate\Foundation\Console;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'make:repository')]
class RepositoryMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:repository';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new repository class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Repository';

    /**
     * Collected classes that need to be imported.
     */
    protected array $useStatements = [];

    /**
     * Execute the console command.
     *
     * @throws FileNotFoundException
     */
    public function handle(): ?bool
    {
        $this->useStatements = [];

        $result = $this->createRepository();

        if ($result && $this->option('bind')) {
            $this->registerBinding();
        }

        return 0;
    }

    /**
     * Create repository using pipeline pattern.
     *
     *
     * @throws FileNotFoundException
     */
    protected function createRepository(): bool
    {
        $context = $this->buildContext();

        if (! $this->validateContext($context)) {
            return false;
        }

        $stub = app(Pipeline::class)
            ->send($this->files->get($this->getStub()))
            ->through([
                fn ($stub, $next) => $next($this->processExtends($stub, $context)),
                fn ($stub, $next) => $next($this->processInterface($stub, $context)),
                fn ($stub, $next) => $next($this->processModel($stub, $context)),
                fn ($stub, $next) => $next($this->processUseStatements($stub)),
                fn ($stub, $next) => $next($this->processNamespace($stub, $context)),
                fn ($stub, $next) => $next($this->processClass($stub, $context)),
            ])
            ->thenReturn();

        $this->files->put($context['path'], $stub);
        $this->components->info(sprintf('%s [%s] created successfully.', $this->type, $context['path']));

        return true;
    }

    /**
     * Build the context for repository creation.
     */
    protected function buildContext(): array
    {
        $name = $this->qualifyClass($this->getNameInput());

        $interfaces = $this->option('interface')
            ? array_map('trim', explode(',', $this->option('interface')))
            : [];

        return [
            'name' => $name,
            'path' => $this->getPath($name),
            'extends' => $this->option('extends'),
            'model' => $this->option('model'),
            'interfaces' => $interfaces,
            'force' => $this->option('force'),
        ];
    }

    /**
     * Validate the context before processing.
     */
    protected function validateContext(array $context): bool
    {
        if ($this->alreadyExists($this->getNameInput()) && ! $context['force']) {
            $this->components->error($this->type.' already exists.');

            return false;
        }

        if ($context['extends']) {
            $this->validateExtends($context['extends']);
        }

        if ($context['model']) {
            $this->validateModel($context['model']);
        }

        if ($context['interfaces']) {
            $this->validateInterfaces($context['interfaces']);
        }

        $this->makeDirectory($context['path']);

        return true;
    }

    protected function validateExtends($extends): bool
    {
        if (str_contains($extends, ',')) {
            $this->components->error('PHP does not support multiple inheritance. You can only extend one class.');

            return false;
        }

        $parentClass = $this->resolveParentClass($extends);

        if (! class_exists($parentClass)) {
            $this->components->error("Parent class [{$parentClass}] does not exist.");

            return false;
        }

        $reflection = new \ReflectionClass($parentClass);
        if ($reflection->isInterface()) {
            $this->components->error("[{$parentClass}] is an interface. Use --interface instead of --extends.");

            return false;
        }

        return true;
    }

    protected function validateModel(string $model): bool
    {
        $modelClass = $this->qualifyModel($model);

        if (! class_exists($modelClass)) {
            $this->components->error("Model [{$modelClass}] does not exist.");

            return false;
        }

        return true;
    }

    protected function validateInterfaces(array $interfaces): bool
    {
        foreach ($interfaces as $interface) {
            $interfaceClass = $this->resolveInterface($interface);

            if (! interface_exists($interfaceClass)) {
                $this->components->error("Interface [{$interfaceClass}] does not exist.");

                return false;
            }

            $reflection = new \ReflectionClass($interfaceClass);
            if (! $reflection->isInterface()) {
                $this->components->error("[{$interfaceClass}] is not an interface.");

                return false;
            }
        }

        return true;
    }

    /**
     * Process class extension.
     */
    protected function processExtends(string $stub, array $context): string
    {
        $extends = '';

        if ($context['extends']) {
            $parentClass = $this->resolveParentClass($context['extends']);
            $extends = class_basename($parentClass);
            $this->useStatements[$parentClass] = $parentClass;
        }

        return str_replace('{{ extends }}', $extends ? ' extends '.$extends : '', $stub);
    }

    /**
     * Process interface implementation and methods.
     *
     * @throws \ReflectionException
     */
    protected function processInterface(string $stub, array $context): string
    {
        $implements = '';
        $methods = '';

        if (! empty($context['interfaces'])) {
            $implementsList = [];

            foreach ($context['interfaces'] as $interface) {
                $interfaceClass = $this->resolveInterface($interface);
                $implementsList[] = class_basename($interfaceClass);
                $this->useStatements[$interfaceClass] = $interfaceClass;

                $methods .= $this->extractInterfaceMethods($interfaceClass);
                if ($methods && ! str_ends_with($methods, "\n")) {
                    $methods .= "\n";
                }
            }

            $implements = implode(', ', $implementsList);
        }

        $stub = str_replace('{{ implements }}', $implements ? ' implements '.$implements : '', $stub);
        $stub = str_replace('{{ methods }}', trim($methods), $stub);

        return $stub;
    }

    /**
     * Process model dependency injection.
     */
    protected function processModel(string $stub, array $context): string
    {
        $constructor = '';

        if ($context['model']) {
            $modelClass = $this->qualifyModel($context['model']);
            $modelName = class_basename($modelClass);
            $this->useStatements[$modelClass] = $modelClass;

            $constructor = "    /**\n";
            $constructor .= "     * Create a new repository instance.\n";
            $constructor .= "     */\n";
            $constructor .= "    public function __construct(\n";
            $constructor .= "        protected {$modelName} \$model\n";
            $constructor .= "    ) {}\n";
        }

        return str_replace('{{ constructor }}', $constructor, $stub);
    }

    /**
     * Process use statements.
     */
    protected function processUseStatements(string $stub): string
    {
        $useStatements = $this->generateUseStatements();

        return str_replace('{{ use }}', $useStatements, $stub);
    }

    /**
     * Process namespace replacement.
     */
    protected function processNamespace(string $stub, array $context): string
    {
        $this->replaceNamespace($stub, $context['name']);

        return $stub;
    }

    /**
     * Process class name replacement.
     */
    protected function processClass(string $stub, array $context): string
    {
        return $this->replaceClass($stub, $context['name']);
    }

    /**
     * Register the repository binding in the service provider.
     *
     * @throws FileNotFoundException
     */
    protected function registerBinding(): void
    {
        $interfaces = $this->option('interface')
            ? array_map('trim', explode(',', $this->option('interface')))
            : [];

        if (empty($interfaces)) {
            $this->components->warn('No interface specified. Skipping service provider binding.');

            return;
        }

        $providerPath = app_path('Providers/AppServiceProvider.php');

        if (! $this->files->exists($providerPath)) {
            $this->components->error('AppServiceProvider not found.');

            return;
        }

        $providerContent = $this->files->get($providerPath);
        $repositoryClass = $this->qualifyClass($this->getNameInput());
        $repositoryName = class_basename($repositoryClass);

        $bindings = [];
        foreach ($interfaces as $interface) {
            $interfaceClass = $this->resolveInterface($interface);
            $interfaceName = class_basename($interfaceClass);

            $binding = "\$this->app->bind({$interfaceName}::class, {$repositoryName}::class);";

            if (str_contains($providerContent, $binding)) {
                $this->components->info("Binding for [{$interfaceName}] already exists.");
                continue;
            }

            $bindings[] = [
                'interface' => $interfaceClass,
                'repository' => $repositoryClass,
                'interfaceName' => $interfaceName,
                'repositoryName' => $repositoryName,
                'binding' => $binding,
            ];
        }

        if (empty($bindings)) {
            return;
        }

        $useStatements = $this->generateProviderUseStatements($bindings, $providerContent);
        if ($useStatements) {
            $providerContent = $this->addUseStatementsToProvider($providerContent, $useStatements);
        }

        $providerContent = $this->addBindingsToProvider($providerContent, $bindings);

        $this->files->put($providerPath, $providerContent);

        foreach ($bindings as $binding) {
            $this->components->info("Binding registered: {$binding['interfaceName']} -> {$binding['repositoryName']}");
        }
    }

    /**
     * Generate use statements for the service provider.
     */
    protected function generateProviderUseStatements(array $bindings, string $providerContent): string
    {
        $useStatements = [];

        foreach ($bindings as $binding) {

            $interfaceUse = "use {$binding['interface']};";
            if (! str_contains($providerContent, $interfaceUse)) {
                $useStatements[] = $interfaceUse;
            }

            $repositoryUse = "use {$binding['repository']};";
            if (! str_contains($providerContent, $repositoryUse)) {
                $useStatements[] = $repositoryUse;
            }
        }

        return implode("\n", $useStatements);
    }

    /**
     * Add use statements to the service provider.
     */
    protected function addUseStatementsToProvider(string $content, string $useStatements): string
    {
        preg_match_all('/^use .+;$/m', $content, $matches, PREG_OFFSET_CAPTURE);

        if (! empty($matches[0])) {
            $lastUse = end($matches[0]);
            $position = $lastUse[1] + strlen($lastUse[0]);

            return substr_replace($content, "\n".$useStatements, $position, 0);
        }

        return preg_replace(
            '/(namespace .+;)/',
            "$1\n\n".$useStatements,
            $content,
            1
        );
    }

    /**
     * Add bindings to the service provider's register method.
     */
    protected function addBindingsToProvider(string $content, array $bindings): string
    {
        $bindingCode = "\n";
        foreach ($bindings as $binding) {
            $bindingCode .= "        {$binding['binding']}\n";
        }

        if (preg_match('/public function register\(\).*?\{/s', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $position = $matches[0][1] + strlen($matches[0][0]);

            return substr_replace($content, $bindingCode, $position, 0);
        }

        $registerMethod = "\n    /**\n";
        $registerMethod .= "     * Register any application services.\n";
        $registerMethod .= "     */\n";
        $registerMethod .= "    public function register(): void\n";
        $registerMethod .= '    {'.$bindingCode;
        $registerMethod .= "    }\n";

        if (preg_match('/public function boot\(\)/', $content)) {
            $content = preg_replace(
                '/(public function boot\(\))/',
                $registerMethod."\n    $1",
                $content,
                1
            );
        } else {
            $content = preg_replace(
                '/(\n}\s*$)/',
                $registerMethod.'$1',
                $content,
                1
            );
        }

        return $content;
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\\Repositories';
    }

    /**
     * Resolve the interface path to a fully-qualified class name.
     */
    protected function resolveInterface(string $interface): string
    {
        return $this->resolveClass($interface);
    }

    /**
     * Resolve the parent class path to a fully-qualified class name.
     */
    protected function resolveParentClass(string $class): string
    {
        return $this->resolveClass($class);
    }

    /**
     * Resolve a class path to a fully-qualified class name.
     */
    protected function resolveClass(string $class): string
    {
        $class = ltrim($class, '\\/');
        $class = str_replace('/', '\\', $class);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($class, $rootNamespace)) {
            return $class;
        }

        $parts = $this->splitDirectory($class);

        return is_dir(app_path($parts['directory']))
            ? $rootNamespace.$parts['directory'].$parts['class']
            : $rootNamespace.$parts['class'];
    }

    /**
     * Split a fully-qualified class name into directory and class name.
     */
    protected function splitDirectory(string $class): array
    {
        if (! str_contains($class, '\\')) {
            return [
                'directory' => '',
                'class' => $class,
            ];
        }

        $pos = strrpos($class, '\\');

        return [
            'directory' => substr($class, 0, $pos + 1),
            'class' => substr($class, $pos + 1),
        ];
    }

    /**
     * Qualify the given model class base name.
     */
    protected function qualifyModel(string $model): string
    {
        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = $this->rootNamespace();

        if (Str::startsWith($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace.'Models\\'.$model
            : $rootNamespace.$model;
    }

    /**
     * Extract methods from interface with proper signatures.
     *
     * @throws \ReflectionException
     */
    protected function extractInterfaceMethods(string $interfaceClass): string
    {
        $reflection = new ReflectionClass($interfaceClass);
        $methods = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->isStatic() || $method->getName() === '__construct') {
                continue;
            }

            $methodName = $method->getName();
            $parameters = $this->getMethodParameters($method);
            $returnType = $this->getReturnType($method);

            $methodSignature = "    public function {$methodName}({$parameters}){$returnType}\n";
            $methodSignature .= "    {\n";
            $methodSignature .= "        // TODO: Implement {$methodName}() method.\n";
            $methodSignature .= "    }\n";

            $methods[] = $methodSignature;
        }

        return implode("\n", $methods);
    }

    /**
     * Get method parameters with types and defaults.
     */
    protected function getMethodParameters(ReflectionMethod $method): string
    {
        $parameters = [];

        foreach ($method->getParameters() as $param) {
            $paramStr = '';

            if ($param->hasType()) {
                $type = $param->getType();
                if ($type instanceof \ReflectionUnionType) {
                    $paramStr .= implode('|', array_map(fn ($t) => $this->formatType($t), $type->getTypes()));
                } else {
                    $paramStr .= $this->formatType($type);
                }
                $paramStr .= ' ';
            }

            if ($param->isVariadic()) {
                $paramStr .= '...';
            }

            if ($param->isPassedByReference()) {
                $paramStr .= '&';
            }

            $paramStr .= '$'.$param->getName();

            if ($param->isDefaultValueAvailable()) {
                $default = $param->getDefaultValue();
                if (is_string($default)) {
                    $paramStr .= " = '{$default}'";
                } elseif (is_bool($default)) {
                    $paramStr .= $default ? ' = true' : ' = false';
                } elseif (is_null($default)) {
                    $paramStr .= ' = null';
                } elseif (is_array($default)) {
                    $paramStr .= ' = []';
                } else {
                    $paramStr .= " = {$default}";
                }
            }

            $parameters[] = $paramStr;
        }

        return implode(', ', $parameters);
    }

    /**
     * Get method return type.
     */
    protected function getReturnType(ReflectionMethod $method): string
    {
        if (! $method->hasReturnType()) {
            return '';
        }

        $returnType = $method->getReturnType();

        if ($returnType instanceof \ReflectionUnionType) {
            $types = array_map(fn ($t) => $this->formatType($t), $returnType->getTypes());

            return ': '.implode('|', $types);
        }

        return ': '.$this->formatType($returnType);
    }

    /**
     * Format reflection type to string.
     */
    protected function formatType(\ReflectionType $type): string
    {
        if ($type->isBuiltin()) {
            return $type->getName();
        }

        $name = $type->getName();

        $this->useStatements[$name] = $name;

        return class_basename($name);
    }

    /**
     * Generate use statements from collected classes.
     */
    protected function generateUseStatements(): string
    {
        if (empty($this->useStatements)) {
            return '';
        }

        $uses = array_values($this->useStatements);
        sort($uses);

        $statements = array_map(function ($class) {
            return "use {$class};";
        }, $uses);

        return implode("\n", $statements);
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/repository.stub');
    }

    /**
     * Resolve the fully-qualified path to the stub.
     */
    protected function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the console command options.
     */
    protected function getOptions(): array
    {
        return [
            ['interface', 'i', InputOption::VALUE_OPTIONAL, 'Generate repository implementing the given interface(s). Separate multiple interfaces with commas'],
            ['extends', 'e', InputOption::VALUE_OPTIONAL, 'Generate repository extending the given class'],
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'Inject the given model into the repository constructor'],
            ['bind', 'b', InputOption::VALUE_NONE, 'Automatically register the repository binding in AppServiceProvider'],
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the repository already exists'],
        ];
    }
}
