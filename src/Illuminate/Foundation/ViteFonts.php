<?php

namespace Illuminate\Foundation;

class ViteFonts
{
    /**
     * The cached font manifests.
     *
     * @var array<string, array<string, mixed>>
     */
    protected static $manifests = [];

    /**
     * Read the font manifest for the given configuration.
     *
     * @param  bool  $isHot
     * @param  string  $buildDirectory
     * @param  string  $manifestFilename
     * @param  string  $hotFile
     * @return array<string, mixed>|null
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function manifest(bool $isHot, string $buildDirectory, string $manifestFilename, string $hotFile)
    {
        $path = $isHot
            ? dirname($hotFile).'/fonts-manifest.dev.json'
            : public_path($buildDirectory.'/'.$manifestFilename);

        return $this->readManifest($path);
    }

    /**
     * Read and decode a manifest file.
     *
     * @param  string  $path
     * @return array<string, mixed>|null
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    protected function readManifest(string $path)
    {
        if (isset(static::$manifests[$path])) {
            return static::$manifests[$path];
        }

        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        $manifest = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ViteException("The font manifest at [{$path}] is not valid JSON.");
        }

        return static::$manifests[$path] = $manifest;
    }

    /**
     * Resolve the CSS content from the manifest.
     *
     * @param  array<string, mixed>  $manifest
     * @param  list<string>|null  $aliases
     * @param  string  $buildDirectory
     * @return string
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function resolveStyleContent(array $manifest, ?array $aliases, string $buildDirectory)
    {
        $style = $manifest['style'] ?? null;

        return match (true) {
            $style === null => '',
            $aliases !== null => $this->resolveFilteredStyleContent($style, $aliases),
            isset($style['inline']) => $style['inline'],
            isset($style['file']) => $this->readStyleFile($buildDirectory, $style['file']),
            default => '',
        };
    }

    /**
     * Resolve filtered CSS content using per-alias fragments from the manifest.
     *
     * @param  array{inline?: string, file?: string, familyStyles?: array<string, string>, variables?: array<string, string>}  $style
     * @param  list<string>  $aliases
     * @return string
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    protected function resolveFilteredStyleContent(array $style, array $aliases)
    {
        $familyStyles = $style['familyStyles'] ?? [];
        $variables = $style['variables'] ?? [];

        if (! is_array($familyStyles)) {
            throw new ViteException(
                'The font manifest [style.familyStyles] must be an object keyed by alias; the manifest was likely produced by an incompatible plugin version.'
            );
        }

        if (! is_array($variables)) {
            throw new ViteException(
                'The font manifest [style.variables] must be an object keyed by alias; the manifest was likely produced by an incompatible plugin version.'
            );
        }

        $parts = [];

        foreach ($aliases as $alias) {
            if (isset($familyStyles[$alias])) {
                $parts[] = $familyStyles[$alias];
            }
        }

        if ($variables !== []) {
            $parts[] = $this->filterVariables($variables, $aliases);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Build a `:root` block containing only the CSS variables for the given aliases.
     *
     * @param  array<string, string>  $variables
     * @param  list<string>  $aliases  List of aliases in desired emission order.
     * @return string
     */
    protected function filterVariables(array $variables, array $aliases)
    {
        $declarations = [];

        foreach ($aliases as $alias) {
            if (isset($variables[$alias])) {
                $declarations[] = '  '.$variables[$alias];
            }
        }

        if ($declarations === []) {
            return '';
        }

        return ":root {\n".implode("\n", $declarations)."\n}";
    }

    /**
     * Read a CSS file from the build directory.
     *
     * @param  string  $buildDirectory
     * @param  string  $file
     * @return string
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    protected function readStyleFile(string $buildDirectory, string $file)
    {
        $path = public_path($buildDirectory.'/'.$file);

        if (! is_file($path)) {
            throw new ViteException("Unable to locate font CSS file from manifest: {$path}.");
        }

        return file_get_contents($path);
    }

    /**
     * Validate the font manifest structure.
     *
     * @param  array<string, mixed>  $manifest
     * @return void
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function ensureValidManifest(array $manifest)
    {
        if (! isset($manifest['version'])) {
            throw new ViteException('The font manifest is missing the [version] key.');
        }

        if ($manifest['version'] !== 1) {
            throw new ViteException("Unsupported font manifest version [{$manifest['version']}]. Supported versions: 1.");
        }

        if (! isset($manifest['families']) || ! is_array($manifest['families'])) {
            throw new ViteException('The font manifest is missing the [families] key.');
        }
    }

    /**
     * Validate that the requested aliases exist in the manifest.
     *
     * @param  list<string>  $aliases
     * @param  array<string, mixed>  $manifest
     * @return void
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function ensureValidFamilies(array $aliases, array $manifest)
    {
        $available = array_keys($manifest['families'] ?? []);

        foreach ($aliases as $alias) {
            if (! in_array($alias, $available, true)) {
                throw new ViteException(
                    "Font alias [{$alias}] is not defined in the font manifest. Available aliases: ".implode(', ', $available).'.'
                );
            }
        }
    }

    /**
     * Validate that each preload entry contains the required keys.
     *
     * @param  list<array<string, string>>  $preloads
     * @param  bool  $isHot
     * @return void
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function ensureValidPreloads(array $preloads, bool $isHot)
    {
        $urlKey = $isHot ? 'url' : 'file';

        foreach ($preloads as $index => $preload) {
            if (! isset($preload['alias'])) {
                throw new ViteException("Font manifest preload entry [{$index}] is missing the [alias] key.");
            }

            if (! isset($preload[$urlKey])) {
                throw new ViteException("Font manifest preload entry [{$index}] for alias [{$preload['alias']}] is missing the [{$urlKey}] key.");
            }
        }
    }

    /**
     * Flush cached manifests.
     *
     * @return void
     */
    public function flush()
    {
        static::$manifests = [];
    }
}
