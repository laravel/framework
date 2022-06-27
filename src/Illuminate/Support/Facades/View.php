<?php

namespace Illuminate\Support\Facades;

/**
 * @method static void addExtension(string $extension, string $engine, \Closure|null $resolver = null)
 * @method static void addLocation(string $location)
 * @method static void addLoop(\Countable|array $data)
 * @method static \Illuminate\View\Factory addNamespace(string $namespace, string|array $hints)
 * @method static string appendSection()
 * @method static void callComposer(\Illuminate\Contracts\View\View $view)
 * @method static void callCreator(\Illuminate\Contracts\View\View $view)
 * @method static array composer(array|string $views, \Closure|string $callback)
 * @method static array composers(array $composers)
 * @method static array creator(array|string $views, \Closure|string $callback)
 * @method static void decrementRender()
 * @method static bool doneRendering()
 * @method static void endSlot()
 * @method static bool exists(string $view)
 * @method static \Illuminate\Contracts\View\View file(string $path, \Illuminate\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static \Illuminate\Contracts\View\View first(array $views, \Illuminate\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static void flushFinderCache()
 * @method static void flushMacros()
 * @method static void flushSections()
 * @method static void flushStacks()
 * @method static void flushState()
 * @method static void flushStateIfDoneRendering()
 * @method static mixed|null getConsumableComponentData(string $key, mixed $default = null)
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \Illuminate\Contracts\Events\Dispatcher getDispatcher()
 * @method static \Illuminate\Contracts\View\Engine getEngineFromPath(string $path)
 * @method static \Illuminate\View\Engines\EngineResolver getEngineResolver()
 * @method static array getExtensions()
 * @method static \Illuminate\View\ViewFinderInterface getFinder()
 * @method static \stdClass|null getLastLoop()
 * @method static array getLoopStack()
 * @method static mixed getSection(string $name, string|null $default = null)
 * @method static array getSections()
 * @method static array getShared()
 * @method static bool hasMacro(string $name)
 * @method static bool hasRenderedOnce(string $id)
 * @method static bool hasSection(string $name)
 * @method static void incrementLoopIndices()
 * @method static void incrementRender()
 * @method static void inject(string $section, string $content)
 * @method static void macro(string $name, object|callable $macro)
 * @method static \Illuminate\Contracts\View\View make(string $view, \Illuminate\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static void markAsRenderedOnce(string $id)
 * @method static void mixin(object $mixin, bool $replace = true)
 * @method static string parentPlaceholder(string $section = '')
 * @method static void popLoop()
 * @method static \Illuminate\View\Factory prependNamespace(string $namespace, string|array $hints)
 * @method static string renderComponent()
 * @method static string renderEach(string $view, array $data, string $iterator, string $empty = 'raw|')
 * @method static string renderTranslation()
 * @method static string renderUnless(bool $condition, string $view, \Illuminate\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static string renderWhen(bool $condition, string $view, \Illuminate\Contracts\Support\Arrayable|array $data = [], array $mergeData = [])
 * @method static \Illuminate\View\Factory replaceNamespace(string $namespace, string|array $hints)
 * @method static bool sectionMissing(string $name)
 * @method static void setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static void setDispatcher(\Illuminate\Contracts\Events\Dispatcher $events)
 * @method static void setFinder(\Illuminate\View\ViewFinderInterface $finder)
 * @method static mixed share(array|string $key, mixed|null $value = null)
 * @method static mixed shared(string $key, mixed $default = null)
 * @method static void slot(string $name, string|null $content = null, array $attributes = [])
 * @method static void startComponent(\Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|\Closure|string $view, array $data = [])
 * @method static void startComponentFirst(array $names, array $data = [])
 * @method static void startPrepend(string $section, string $content = '')
 * @method static void startPush(string $section, string $content = '')
 * @method static void startSection(string $section, string|null $content = null)
 * @method static void startTranslation(array $replacements = [])
 * @method static string stopPrepend()
 * @method static string stopPush()
 * @method static string stopSection(bool $overwrite = false)
 * @method static string yieldContent(string $section, string $default = '')
 * @method static string yieldPushContent(string $section, string $default = '')
 * @method static string yieldSection()
 *
 * @see \Illuminate\View\Factory
 */
class View extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'view';
    }
}
