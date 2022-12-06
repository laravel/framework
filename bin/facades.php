<?php

require __DIR__.'/../vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Conditionable;
use Symfony\Component\Finder\Finder;

/*
 * Update the docblocks:
 * $ php -f ./bin/facades.php
 *
 * Lint the docblocks:
 * $ php -f ./bin/facades.php -- --lint
 */

$linting = in_array('--lint', $argv);

$finder = (new Finder)
    ->in(__DIR__.'/../src/Illuminate/Support/Facades')
    ->notName('Facade.php');

resolveFacades($finder)->each(function ($facade) use ($linting) {
    $proxies = resolveDocSees($facade);

    // Build a list of methods that are available on the Facade...

    $resolvedMethods = $proxies->map(fn ($fqcn) => new ReflectionClass($fqcn))
        ->flatMap(fn ($class) => [$class, ...resolveDocMixins($class)])
        ->flatMap(resolveMethods(...))
        ->reject(isMagic(...))
        ->reject(isDeprecated(...))
        ->reject(fulfillsBuiltinInterface(...))
        ->reject(fn ($method) => conflictsWithFacade($facade, $method))
        ->unique(resolveName(...))
        ->map(normaliseDetails(...));

    // Prepare the @method docblocks...

    $methods = $resolvedMethods->map(function ($method) {
        if (is_string($method)) {
            return " * @method static {$method}";
        }

        $parameters = $method['parameters']->map(function ($parameter) {
            $rest = $parameter['variadic'] ? '...' : '';

            $default = $parameter['optional'] ? ' = '.resolveDefaultValue($parameter) : '';

            return "{$parameter['type']} {$rest}{$parameter['name']}{$default}";
        });

        return " * @method static {$method['returns']} {$method['name']}({$parameters->join(', ')})";
    });

    // Fix: ensure we keep the references to the Carbon library on the Date Facade...

    if ($facade->getName() === Date::class) {
        $methods->prepend(' *')
                ->prepend(' * @see https://github.com/briannesbitt/Carbon/blob/master/src/Carbon/Factory.php')
                ->prepend(' * @see https://carbon.nesbot.com/docs/');
    }

    // Generate the docblock...

    $docblock = <<< PHP
    /**
    {$methods->join(PHP_EOL)}
     *
    {$proxies->map(fn ($class) => " * @see {$class}")->join(PHP_EOL)}
     */
    PHP;

    if (($facade->getDocComment() ?: '') === $docblock) {
        return;
    }

    if ($linting) {
        echo "Did not find expected docblock for [{$facade->getName()}].".PHP_EOL.PHP_EOL;
        echo $docblock.PHP_EOL.PHP_EOL;
        echo 'Run the following command to update your docblocks locally:'.PHP_EOL.'php -f bin/facades.php';
        exit(1);
    }

    // Update the facade docblock...

    echo "Updating docblock for [{$facade->getName()}].".PHP_EOL;
    $contents = file_get_contents($facade->getFileName());
    $contents = Str::replace($facade->getDocComment(), $docblock, $contents);
    file_put_contents($facade->getFileName(), $contents);
});

echo 'Done.';
exit(0);

/**
 * Resolve the facades from the given directory.
 *
 * @param  \Symfony\Component\Finder\Finder  $finder
 * @return \Illuminate\Support\Collection<\ReflectionClass>
 */
function resolveFacades($finder)
{
    return collect($finder)
        ->map(fn ($file) => $file->getBaseName('.php'))
        ->map(fn ($name) => "\\Illuminate\\Support\\Facades\\{$name}")
        ->map(fn ($class) => new ReflectionClass($class));
}

/**
 * Resolve the classes referenced in the @see docblocks.
 *
 * @param  \ReflectionClass  $class
 * @return \Illuminate\Support\Collection<class-string>
 */
function resolveDocSees($class)
{
    return resolveDocTags($class->getDocComment() ?: '', '@see')
        ->reject(fn ($tag) => Str::startsWith($tag, 'https://'));
}

/**
 * Resolve the classes referenced methods in the @methods docblocks.
 *
 * @param  \ReflectionClass  $class
 * @return \Illuminate\Support\Collection<string>
 */
function resolveDocMethods($class)
{
    return resolveDocTags($class->getDocComment() ?: '', '@method')
        ->map(fn ($tag) => Str::squish($tag))
        ->map(fn ($tag) => Str::before($tag, ')').')');
}

/**
 * Resolve the parameters type from the @param docblocks.
 *
 * @param  \ReflectionMethodDecorator  $method
 * @param  \ReflectionParameter  $parameter
 * @return string|null
 */
function resolveDocParamType($method, $parameter)
{
    $tag = resolveDocTags($method->getDocComment() ?: '', '@param')
        ->first(fn ($tag) => preg_match('/\$'.$parameter->getName().'\b/', $tag) === 1);

    // As we didn't find a param type, we will now recursivly check if the prototype has a value specified...

    if ($tag === null) {
        try {
            $prototype = new ReflectionMethodDecorator($method->getPrototype(), $method->sourceClass()->getName());

            return resolveDocParamType($prototype, $parameter);
        } catch (Throwable) {
            return null;
        }
    }

    // Strip the rest operator, variable name, and desription from the tag...

    $types = Str::of($tag)
        ->beforeLast("...\${$parameter->getName()}")
        ->beforeLast("\${$parameter->getName()}")
        ->trim()
        ->toString();

    // Replace references to '$this', 'static', or 'self' with the implementations FQCN...

    $types = Str::of($types)
        ->replace(['$this', 'static'], '\\'.$method->sourceClass()->getName())
        ->replace('self', '\\'.$method->getDeclaringClass()->getName())
        ->toString();

    return stripGenerics($method, $types);
}

/**
 * Resolve the return type from the @return docblock.
 *
 * @param  \ReflectionMethodDecorator  $method
 * @return string|null
 */
function resolveReturnDocType($method)
{
    $types = resolveDocTags($method->getDocComment() ?: '', '@return')->first();

    if ($types === null) {
        return null;
    }

    // Strip the return description, but don't strip array generics...

    if (Str::containsAll($types, ['<', '>'])) {
        $types = Str::beforeLast($types, '>').'>';
    } elseif (Str::contains($types, ' ')) {
        $types = Str::before($types, ' ');
    }

    // Replace references to '$this', 'static', or 'self' with the implementations FQCN...

    $types = Str::of($types)
        ->replace(['$this', 'static'], '\\'.$method->sourceClass()->getName())
        ->replace('self', '\\'.$method->getDeclaringClass()->getName())
        ->toString();

    return stripGenerics($method, $types);
}

/**
 * Remove generics from the type.
 *
 * Unfortunately the @template tag is not currently working with the @method
 * docblocks, so we are stripping them out.
 *
 * @param  \ReflectionMethodDecorator  $method
 * @param  string  $types
 * @return string
 */
function stripGenerics($method, $types)
{
    if (
        'enum' === $method->getName() &&
        Request::class === $method->getDeclaringClass()->getName()
    ) {
        return Str::replace('TEnum', 'object', $types);
    }

    if (
        'when' === $method->getName() &&
        in_array(Conditionable::class, class_uses_recursive($method->getDeclaringClass()->getName()))
    ) {
        return Str::of($types)
            ->replace(['TWhenParameter', 'TWhenReturnType'], 'mixed')
            ->replace('mixed|null', 'mixed')
            ->toString();
    }

    if (
        'unless' === $method->getName() &&
        in_array(Conditionable::class, class_uses_recursive($method->getDeclaringClass()->getName()))
    ) {
        return Str::of($types)
            ->replace(['TUnlessParameter', 'TUnlessReturnType'], 'mixed')
            ->replace('mixed|null', 'mixed')
            ->toString();
    }

    return $types;
}

/**
 * Resolve the declared type.
 *
 * @param  \ReflectionType|null  $type
 * @return string|null
 */
function resolveType($type)
{
    if ($type instanceof ReflectionIntersectionType) {
        return collect($type->getTypes())
            ->map(resolveType(...))
            ->filter()
            ->join('&');
    }

    if ($type instanceof ReflectionUnionType) {
        return collect($type->getTypes())
            ->map(resolveType(...))
            ->filter()
            ->join('|');
    }

    if ($type instanceof ReflectionNamedType && $type->getName() === 'null') {
        return ($type->isBuiltin() ? '' : '\\').$type->getName();
    }

    if ($type instanceof ReflectionNamedType && $type->getName() !== 'null') {
        return ($type->isBuiltin() ? '' : '\\').$type->getName().($type->allowsNull() ? '|null' : '');
    }

    return null;
}

/**
 * Resolve the docblock tags.
 *
 * @param  string  $docblock
 * @param  string  $tag
 * @return \Illuminate\Support\Collection<string>
 */
function resolveDocTags($docblock, $tag)
{
    return Str::of($docblock)
        ->explode("\n")
        ->skip(1)
        ->reverse()
        ->skip(1)
        ->reverse()
        ->map(fn ($line) => ltrim($line, ' \*'))
        ->filter(fn ($line) => Str::startsWith($line, $tag))
        ->map(fn ($line) => Str::of($line)->after($tag)->trim()->toString())
        ->values();
}

/**
 * Recursivly resolve docblock mixins.
 *
 * @param  \ReflectionClass  $class
 * @return \Illuminate\Support\Collection<\ReflectionClass>
 */
function resolveDocMixins($class)
{
    return resolveDocTags($class->getDocComment() ?: '', '@mixin')
        ->map(fn ($mixin) => new ReflectionClass($mixin))
        ->flatMap(fn ($mixin) => [$mixin, ...resolveDocMixins($mixin)]);
}

/**
 * Determine if the method is magic.
 *
 * @param  \ReflectionMethod|string  $method
 * @return bool
 */
function isMagic($method)
{
    return Str::startsWith(is_string($method) ? $method : $method->getName(), '__');
}

/**
 * Determine if the method is deprecated.
 *
 * @param  \ReflectionMethod|string  $method
 * @return bool
 */
function isDeprecated($method)
{
    return ! is_string($method) && $method->isDeprecated();
}

/**
 * Determine if the method is for a builtin contract.
 *
 * @param  \ReflectionMethodDecorator|string  $method
 * @return bool
 */
function fulfillsBuiltinInterface($method)
{
    if (is_string($method)) {
        return false;
    }

    if ($method->sourceClass()->implementsInterface(ArrayAccess::class)) {
        return in_array($method->getName(), ['offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset']);
    }

    return false;
}

/**
 * Resolve the methods name.
 *
 * @param  \ReflectionMethod|string  $method
 * @return string
 */
function resolveName($method)
{
    return is_string($method)
        ? Str::of($method)->after(' ')->before('(')->toString()
        : $method->getName();
}

/**
 * Resolve the classes methods.
 *
 * @param  \ReflectionClass  $class
 * @return \Illuminate\Support\Collection<\ReflectionMethodDecorator|string>
 */
function resolveMethods($class)
{
    return collect($class->getMethods(ReflectionMethod::IS_PUBLIC))
        ->map(fn ($method) => new ReflectionMethodDecorator($method, $class->getName()))
        ->merge(resolveDocMethods($class));
}

/**
 * Determine if the given method conflicts with a Facade method.
 *
 * @param  \ReflectionClass  $facade
 * @param  \ReflectionMethod|string  $method
 * @return bool
 */
function conflictsWithFacade($facade, $method)
{
    return collect($facade->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC))
        ->map(fn ($method) => $method->getName())
        ->contains(is_string($method) ? $method : $method->getName());
}

/**
 * Normalise the method details into a easier format to work with.
 *
 * @param  \ReflectionMethodDecorator|string  $method
 * @return array|string
 */
function normaliseDetails($method)
{
    return is_string($method) ? $method : [
        'name' => $method->getName(),
        'parameters' => collect($method->getParameters())
            ->map(fn ($parameter) => [
                'name' => '$'.$parameter->getName(),
                'optional' => $parameter->isOptional() && ! $parameter->isVariadic(),
                'default' => $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : "❌ Unknown default for [{$parameter->getName()}] in [{$parameter->getDeclaringClass()?->getName()}::{$parameter->getDeclaringFunction()->getName()}] ❌",
                'variadic' => $parameter->isVariadic(),
                'type' => resolveDocParamType($method, $parameter) ?? resolveType($parameter->getType()) ?? 'void',
            ]),
        'returns' => resolveReturnDocType($method) ?? resolveType($method->getReturnType()) ?? 'void',
    ];
}

/**
 * Resolve the default value for the parameter.
 *
 * @param  array  $parameter
 * @return string
 */
function resolveDefaultValue($parameter)
{
    // Reflection limitation fix for:
    // - Illuminate\Filesystem\Filesystem::ensureDirectoryExists()
    // - Illuminate\Filesystem\Filesystem::makeDirectory()
    if ($parameter['name'] === '$mode' && $parameter['default'] === 493) {
        return '0755';
    }

    $default = json_encode($parameter['default']);

    return Str::of($default === false ? 'unknown' : $default)
        ->replace('"', "'")
        ->replace('\\/', '/')
        ->toString();
}

/**
 * @mixin \ReflectionMethod
 */
class ReflectionMethodDecorator
{
    /**
     * @param  \ReflectionMethod  $method
     * @param  class-string  $sourceClass
     */
    public function __construct(private $method, private $sourceClass)
    {
        //
    }

    /**
     * @param  string  $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->method->{$name}(...$arguments);
    }

    /**
     * @return \ReflectionMethod
     */
    public function toBase()
    {
        return $this->method;
    }

    /**
     * @return \ReflectionClass
     */
    public function sourceClass()
    {
        return new ReflectionClass($this->sourceClass);
    }
}
