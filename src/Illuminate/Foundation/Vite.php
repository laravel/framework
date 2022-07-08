<?php

namespace Illuminate\Foundation;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class Vite
{
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

        if ($hotServer = $this->hotServer()) {
            return new HtmlString(
                $entrypoints
                    ->map(fn ($entrypoint) => $this->makeTag("$hotServer/$entrypoint"))
                    ->prepend($this->makeScriptTag("$hotServer/@vite/client"))
                    ->join('')
            );
        }

        $tags = collect();
        $manifest = $this->manifestContents($buildDirectory);

        foreach ($entrypoints as $entrypoint) {
            if (! isset($manifest[$entrypoint])) {
                throw new \Exception("Unable to locate file in Vite manifest: {$entrypoint}.");
            }

            $tags->push($this->makeTag(asset("$buildDirectory/{$manifest[$entrypoint]['file']}")));

            if (isset($manifest[$entrypoint]['css'])) {
                foreach ($manifest[$entrypoint]['css'] as $css) {
                    $tags->push($this->makeStylesheetTag(asset("$buildDirectory/$css")));
                }
            }

            if (isset($manifest[$entrypoint]['imports'])) {
                foreach ($manifest[$entrypoint]['imports'] as $import) {
                    if (isset($manifest[$import]['css'])) {
                        foreach ($manifest[$import]['css'] as $css) {
                            $tags->push($this->makeStylesheetTag(asset("$buildDirectory/$css")));
                        }
                    }
                }
            }
        }

        [$stylesheets, $scripts] = $tags->partition(fn ($tag) => str_starts_with($tag, '<link'));

        return new HtmlString($stylesheets->join('').$scripts->join(''));
    }

    /**
     * Retrieve a single Vite absolute resource URL.
     *
     * @param  string  $resourcePath
     * @param  string  $buildDirectory
     * @return string
     *
     * @throws \Exception
     */
    public function resourceUrl($resourcePath, $buildDirectory = 'build')
    {
        if ($hotServer = $this->hotServer()) {
            return "$hotServer/$resourcePath";
        }

        $manifest = $this->manifestContents($buildDirectory);

        if (! isset($manifest[$resourcePath]['file'])) {
            throw new \Exception('Unknown Vite entrypoint '.$resourcePath);
        }

        return asset(Str::start($buildDirectory.'/'.$manifest[$resourcePath]['file'], '/'));
    }

    /**
     * Generate React refresh runtime script.
     *
     * @return \Illuminate\Support\HtmlString|void
     */
    public function reactRefresh()
    {
        if (! $hotServer = $this->hotServer()) {
            return;
        }

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
                $hotServer
            )
        );
    }

    /**
     * Retrieve our manifest file contents.
     *
     * @param  string  $buildDirectory
     * @return array
     *
     * @throws \Exception
     */
    protected function manifestContents($buildDirectory = 'build')
    {
        static $manifests = [];

        $manifestPath = public_path($buildDirectory.'/manifest.json');

        if (! isset($manifests[$manifestPath])) {
            if (! is_file($manifestPath)) {
                throw new \Exception("Vite manifest not found at: {$manifestPath}");
            }

            $manifests[$manifestPath] = json_decode(file_get_contents($manifestPath), true);
        }

        return $manifests[$manifestPath];
    }

    /**
     * Return the path to the Vite hot-reload server when available.
     *
     * @return string|null
     */
    protected function hotServer(): string|null
    {
        if (is_file(public_path('/hot'))) {
            return rtrim(file_get_contents(public_path('/hot')));
        }

        return null;
    }

    /**
     * Generate an appropriate tag for the given URL.
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
     * @param  string  $url
     * @return string
     */
    protected function makeScriptTag($url)
    {
        return sprintf('<script type="module" src="%s"></script>', $url);
    }

    /**
     * Generate a stylesheet tag for the given URL.
     *
     * @param  string  $url
     * @return string
     */
    protected function makeStylesheetTag($url)
    {
        return sprintf('<link rel="stylesheet" href="%s" />', $url);
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
}
