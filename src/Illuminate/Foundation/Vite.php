<?php

namespace Illuminate\Foundation;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Vite
{
    /**
     * The Content Security Policy nonce to apply to all generated tags.
     *
     * @var string|null
     */
    protected $nonce;

    /**
     * The key to check for integrity hashes within the manifest.
     *
     * @var string|false
     */
    protected $integrityKey = 'integrity';

    /**
     * The script tag attributes resolvers.
     *
     * @var array
     */
    protected $scriptTagAttributesResolvers = [];

    /**
     * The style tag attributes resolvers.
     *
     * @var array
     */
    protected $styleTagAttributesResolvers = [];

    /**
     * Get the Content Security Policy nonce applied to all generated tags.
     *
     * @return string|null
     */
    public function cspNonce()
    {
        return $this->nonce;
    }

    /**
     * Generate or set a Content Security Policy nonce to apply to all generated tags.
     *
     * @param  ?string  $nonce
     * @return string
     */
    public function useCspNonce($nonce = null)
    {
        return $this->nonce = $nonce ?? Str::random(40);
    }

    /**
     * Use the given key to detect integrity hashes in the manifest.
     *
     * @param  string|false  $key
     * @return $this
     */
    public function useIntegrityKey($key)
    {
        $this->integrityKey = $key;

        return $this;
    }

    /**
     * Use the given callback to resolve attributes for script tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useScriptTagAttributes($attributes)
    {
        if (! is_callable($attributes)) {
            $attributes = fn () => $attributes;
        }

        $this->scriptTagAttributesResolvers[] = $attributes;

        return $this;
    }

    /**
     * Use the given callback to resolve attributes for style tags.
     *
     * @param  (callable(string, string, ?array, ?array): array)|array  $attributes
     * @return $this
     */
    public function useStyleTagAttributes($attributes)
    {
        if (! is_callable($attributes)) {
            $attributes = fn () => $attributes;
        }

        $this->styleTagAttributesResolvers[] = $attributes;

        return $this;
    }

    /**
     * Generate Vite tags for an entrypoint.
     *
     * @param  string|string[]  $entrypoints
     * @param  string  $buildDirectory
     * @return \Illuminate\Support\HtmlString
     *
     * @throws \Exception
     */
    public function __invoke($entrypoints, $buildDirectory = 'build')
    {
        static $manifests = [];

        $entrypoints = collect($entrypoints);
        $buildDirectory = Str::start($buildDirectory, '/');

        if (is_file(public_path('/hot'))) {
            $url = rtrim(file_get_contents(public_path('/hot')));

            return new HtmlString(
                $entrypoints
                    ->prepend('@vite/client')
                    ->map(fn ($entrypoint) => $this->makeTagForChunk($entrypoint, "{$url}/{$entrypoint}", null, null))
                    ->join('')
            );
        }

        $manifestPath = public_path($buildDirectory.'/manifest.json');

        if (! isset($manifests[$manifestPath])) {
            if (! is_file($manifestPath)) {
                throw new Exception("Vite manifest not found at: {$manifestPath}");
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        $manifest = $manifests[$manifestPath];

        $tags = collect();

        foreach ($entrypoints as $entrypoint) {
            if (! isset($manifest[$entrypoint])) {
                throw new Exception("Unable to locate file in Vite manifest: {$entrypoint}.");
            }

            $tags->push($this->makeTagForChunk(
                $entrypoint,
                asset("{$buildDirectory}/{$manifest[$entrypoint]['file']}"),
                $manifest[$entrypoint],
                $manifest
            ));

            foreach ($manifest[$entrypoint]['css'] ?? [] as $css) {
                $tags->push($this->makeTagForChunk(
                    $entrypoint,
                    asset("{$buildDirectory}/{$css}"),
                    $manifest[$entrypoint],
                    $manifest
                ));
            }

            foreach ($manifest[$entrypoint]['imports'] ?? [] as $import) {
                foreach ($manifest[$import]['css'] ?? [] as $css) {
                    $partialManifest = Collection::make($manifest)->where('file', $css);

                    $tags->push($this->makeTagForChunk(
                        $partialManifest->keys()->first(),
                        asset("{$buildDirectory}/{$css}"),
                        $partialManifest->first(),
                        $manifest
                    ));
                }
            }
        }

        [$stylesheets, $scripts] = $tags->partition(fn ($tag) => str_starts_with($tag, '<link'));

        return new HtmlString($stylesheets->join('').$scripts->join(''));
    }

    /**
     * Make tag for the given chunk.
     *
     * @param  string  $src
     * @param  string  $url
     * @param  ?array  $chunk
     * @param  ?array  $manifest
     * @return string
     */
    protected function makeTagForChunk($src, $url, $chunk, $manifest)
    {
        if (
            $this->nonce === null
            && $this->integrityKey !== false
            && ! array_key_exists($this->integrityKey, $chunk ?? [])
            && $this->scriptTagAttributesResolvers === []
            && $this->styleTagAttributesResolvers === []) {
            return $this->makeTag($url);
        }

        if ($this->isCssPath($url)) {
            return $this->makeStylesheetTagWithAttributes(
                $url,
                $this->resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)
            );
        }

        return $this->makeScriptTagWithAttributes(
            $url,
            $this->resolveScriptTagAttributes($src, $url, $chunk, $manifest)
        );
    }

    /**
     * Resolve the attributes for the chunks generated script tag.
     *
     * @param  string  $src
     * @param  string  $url
     * @param  ?array  $chunk
     * @param  ?array  $manifest
     * @return array
     */
    protected function resolveScriptTagAttributes($src, $url, $chunk, $manifest)
    {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->scriptTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Resolve the attributes for the chunks generated stylesheet tag.
     *
     * @param  string  $src
     * @param  string  $url
     * @param  ?array  $chunk
     * @param  ?array  $manifest
     * @return array
     */
    protected function resolveStylesheetTagAttributes($src, $url, $chunk, $manifest)
    {
        $attributes = $this->integrityKey !== false
            ? ['integrity' => $chunk[$this->integrityKey] ?? false]
            : [];

        foreach ($this->styleTagAttributesResolvers as $resolver) {
            $attributes = array_merge($attributes, $resolver($src, $url, $chunk, $manifest));
        }

        return $attributes;
    }

    /**
     * Generate an appropriate tag for the given URL in HMR mode.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $url
     * @return string
     */
    protected function makeTag($url)
    {
        if ($this->isCssPath($url)) {
            return $this->makeStylesheetTag($url);
        }

        return $this->makeScriptTag($url);
    }

    /**
     * Generate a script tag for the given URL.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $url
     * @return string
     */
    protected function makeScriptTag($url)
    {
        return $this->makeScriptTagWithAttributes($url, []);
    }

    /**
     * Generate a stylesheet tag for the given URL in HMR mode.
     *
     * @deprecated Will be removed in a future Laravel version.
     *
     * @param  string  $url
     * @return string
     */
    protected function makeStylesheetTag($url)
    {
        return $this->makeStylesheetTagWithAttributes($url, []);
    }

    /**
     * Generate a script tag with attributes for the given URL.
     *
     * @param  string  $url
     * @param  array  $attributes
     * @return string
     */
    protected function makeScriptTagWithAttributes($url, $attributes)
    {
        $attributes = $this->parseAttributes(array_merge([
            'type' => 'module',
            'src' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<script '.implode(' ', $attributes).'></script>';
    }

    /**
     * Generate a link tag with attributes for the given URL.
     *
     * @param  string  $url
     * @param  array  $attributes
     * @return string
     */
    protected function makeStylesheetTagWithAttributes($url, $attributes)
    {
        $attributes = $this->parseAttributes(array_merge([
            'rel' => 'stylesheet',
            'href' => $url,
            'nonce' => $this->nonce ?? false,
        ], $attributes));

        return '<link '.implode(' ', $attributes).' />';
    }

    /**
     * Determine whether the given path is a CSS file.
     *
     * @param  string  $path
     * @return bool
     */
    protected function isCssPath($path)
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $path) === 1;
    }

    /**
     * Parse the attributes into key="value" strings.
     *
     * @param  array  $attributes
     * @return array
     */
    protected function parseAttributes($attributes)
    {
        return Collection::make($attributes)
            ->reject(fn ($value, $key) => in_array($value, [false, null], true))
            ->flatMap(fn ($value, $key) => $value === true ? [$key] : [$key => $value])
            ->map(fn ($value, $key) => is_int($key) ? $value : $key.'="'.$value.'"')
            ->values()
            ->all();
    }

    /**
     * Generate React refresh runtime script.
     *
     * @return \Illuminate\Support\HtmlString|void
     */
    public function reactRefresh()
    {
        if (! is_file(public_path('/hot'))) {
            return;
        }

        $url = rtrim(file_get_contents(public_path('/hot')));

        return new HtmlString(
            sprintf(
                <<<'HTML'
                <script type="module">
                    import RefreshRuntime from '%s/@react-refresh'
                    RefreshRuntime.injectIntoGlobalHook(window)
                    window.$RefreshReg$ = () => {}
                    window.$RefreshSig$ = () => (type) => type
                    window.__vite_plugin_react_preamble_installed__ = true
                </script>
                HTML,
                $url
            )
        );
    }
}
