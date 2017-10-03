<?php

namespace Illuminate\Support\Facades;

/**
 * @method static \Illuminate\Contracts\View\View file(string $path, array $data, array $mergeData) Get the evaluated view contents for the given view.
 * @method static \Illuminate\Contracts\View\View make(string $view, array $data, array $mergeData) Get the evaluated view contents for the given view.
 * @method static \Illuminate\Contracts\View\View first(array $views, array $data, array $mergeData) Get the first view that actually exists from the given list.
 * @method static string renderWhen(bool $condition, string $view, array $data, array $mergeData) Get the rendered content of the view based on a given condition.
 * @method static string renderEach(string $view, array $data, string $iterator, string $empty) Get the rendered contents of a partial from a loop.
 * @method static bool exists(string $view) Determine if a given view exists.
 * @method static \Illuminate\Contracts\View\Engine getEngineFromPath(string $path) Get the appropriate view engine for the given path.
 * @method static mixed share(array | string $key, mixed $value) Add a piece of shared data to the environment.
 * @method static void incrementRender() Increment the rendering counter.
 * @method static void decrementRender() Decrement the rendering counter.
 * @method static bool doneRendering() Check if there are no active render operations.
 * @method static void addLocation(string $location) Add a location to the array of view locations.
 * @method static $this addNamespace(string $namespace, string | array $hints) Add a new namespace to the loader.
 * @method static $this prependNamespace(string $namespace, string | array $hints) Prepend a new namespace to the loader.
 * @method static $this replaceNamespace(string $namespace, string | array $hints) Replace the namespace hints for the given namespace.
 * @method static void addExtension(string $extension, string $engine, \Closure $resolver) Register a valid view extension and its engine.
 * @method static void flushState() Flush all of the factory state like sections and stacks.
 * @method static void flushStateIfDoneRendering() Flush all of the section contents if done rendering.
 * @method static array getExtensions() Get the extension to engine bindings.
 * @method static \Illuminate\View\Engines\EngineResolver getEngineResolver() Get the engine resolver instance.
 * @method static \Illuminate\View\ViewFinderInterface getFinder() Get the view finder instance.
 * @method static void setFinder(\Illuminate\View\ViewFinderInterface $finder) Set the view finder instance.
 * @method static void flushFinderCache() Flush the cache of views located by the finder.
 * @method static \Illuminate\Contracts\Events\Dispatcher getDispatcher() Get the event dispatcher instance.
 * @method static void setDispatcher(\Illuminate\Contracts\Events\Dispatcher $events) Set the event dispatcher instance.
 * @method static \Illuminate\Contracts\Container\Container getContainer() Get the IoC container instance.
 * @method static void setContainer(\Illuminate\Contracts\Container\Container $container) Set the IoC container instance.
 * @method static mixed shared(string $key, mixed $default) Get an item from the shared data.
 * @method static array getShared() Get all of the shared data for the environment.
 * @method static void startComponent(string $name, array $data) Start a component rendering process.
 * @method static string renderComponent() Render the current component.
 * @method static void slot(string $name, string | null $content) Start the slot rendering process.
 * @method static void endSlot() Save the slot content for rendering.
 * @method static array creator(array | string $views, \Closure | string $callback) Register a view creator event.
 * @method static array composers(array $composers) Register multiple view composers via an array.
 * @method static array composer(array | string $views, \Closure | string $callback) Register a view composer event.
 * @method static void callComposer(\Illuminate\Contracts\View\View $view) Call the composer for a given view.
 * @method static void callCreator(\Illuminate\Contracts\View\View $view) Call the creator for a given view.
 * @method static void startSection(string $section, string | null $content) Start injecting content into a section.
 * @method static void inject(string $section, string $content) Inject inline content into a section.
 * @method static string yieldSection() Stop injecting content into a section and return its contents.
 * @method static string stopSection(bool $overwrite) Stop injecting content into a section.
 * @method static string appendSection() Stop injecting content into a section and append it.
 * @method static string yieldContent(string $section, string $default) Get the string contents of a section.
 * @method static string parentPlaceholder(string $section) Get the parent placeholder for the current request.
 * @method static bool hasSection(string $name) Check if section exists.
 * @method static mixed getSection(string $name, string $default) Get the contents of a section.
 * @method static array getSections() Get the entire array of sections.
 * @method static void flushSections() Flush all of the sections.
 * @method static void addLoop(\Countable | array $data) Add new loop to the stack.
 * @method static void incrementLoopIndices() Increment the top loop's indices.
 * @method static void popLoop() Pop a loop from the top of the loop stack.
 * @method static \stdClass|null getLastLoop() Get an instance of the last loop in the stack.
 * @method static array getLoopStack() Get the entire loop stack.
 * @method static void startPush(string $section, string $content) Start injecting content into a push section.
 * @method static string stopPush() Stop injecting content into a push section.
 * @method static void startPrepend(string $section, string $content) Start prepending content into a push section.
 * @method static string stopPrepend() Stop prepending content into a push section.
 * @method static string yieldPushContent(string $section, string $default) Get the string contents of a push section.
 * @method static void flushStacks() Flush all of the stacks.
 * @method static void startTranslation(array $replacements) Start a translation block.
 * @method static string renderTranslation() Render the current translation.
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
