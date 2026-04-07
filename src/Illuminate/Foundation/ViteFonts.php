<?php

namespace Illuminate\Foundation;

class ViteFonts
{
    /**
     * The cached font manifests.
     *
     * @var array
     */
    protected static $manifests = [];

    /**
     * Read the font manifest for the given configuration.
     *
     * @param  bool  $isHot
     * @param  string  $buildDirectory
     * @param  string  $manifestFilename
     * @param  string  $hotFile
     * @return array|null
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function manifest($isHot, $buildDirectory, $manifestFilename, $hotFile)
    {
        $path = $isHot
            ? dirname($hotFile).'/fonts-manifest.dev.json'
            : public_path($buildDirectory.'/'.$manifestFilename);

        return $this->readManifest($path);
    }

    /**
     * Resolve the CSS content from the manifest.
     *
     * @param  array  $manifest
     * @param  array|null  $families
     * @param  string  $buildDirectory
     * @return string
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function resolveStyleContent(array $manifest, ?array $families, $buildDirectory)
    {
        $style = $manifest['style'] ?? null;

        return match (true) {
            $style === null => '',
            $families !== null => $this->resolveFilteredStyleContent($style, $families, $manifest['families'] ?? []),
            isset($style['inline']) => $style['inline'],
            isset($style['file']) => $this->readStyleFile($buildDirectory, $style['file']),
            default => '',
        };
    }

    /**
     * Resolve filtered CSS content using per-family fragments from the manifest.
     *
     * @param  array  $style
     * @param  array  $families
     * @param  array  $manifestFamilies
     * @return string
     */
    protected function resolveFilteredStyleContent(array $style, array $families, array $manifestFamilies)
    {
        $familyStyles = $style['familyStyles'] ?? [];
        $variables = $style['variables'] ?? '';

        $parts = [];

        foreach ($families as $family) {
            if (isset($familyStyles[$family])) {
                $parts[] = $familyStyles[$family];
            }
        }

        if ($variables !== '') {
            $parts[] = $this->filterVariables($variables, $families, $manifestFamilies);
        }

        return implode("\n\n", $parts);
    }

    /**
     * Filter a CSS variables block to only include variables for the given families.
     *
     * @param  string  $variables
     * @param  array  $families
     * @param  array  $manifestFamilies
     * @return string
     */
    protected function filterVariables($variables, array $families, array $manifestFamilies)
    {
        $allowedVariables = [];

        foreach ($families as $family) {
            if (isset($manifestFamilies[$family]['variable'])) {
                $allowedVariables[] = $manifestFamilies[$family]['variable'];
            }
        }

        if (empty($allowedVariables)) {
            return '';
        }

        $lines = explode("\n", $variables);
        $filtered = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === ':root {' || $trimmed === '}') {
                $filtered[] = $line;

                continue;
            }

            foreach ($allowedVariables as $variable) {
                if (str_contains($trimmed, $variable.':')) {
                    $filtered[] = $line;

                    break;
                }
            }
        }

        $result = implode("\n", $filtered);

        if (trim($result) === ':root {' || trim($result) === '}' || trim($result) === ":root {\n}") {
            return '';
        }

        return $result;
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
    protected function readStyleFile($buildDirectory, $file)
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
     * @param  array  $manifest
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
     * Validate that the requested families exist in the manifest.
     *
     * @param  array  $families
     * @param  array  $manifest
     * @return void
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function ensureValidFamilies(array $families, array $manifest)
    {
        $available = array_keys($manifest['families'] ?? []);

        foreach ($families as $family) {
            if (! in_array($family, $available, true)) {
                throw new ViteException(
                    "Font family [{$family}] is not defined in the font manifest. Available families: ".implode(', ', $available).'.'
                );
            }
        }
    }

    /**
     * Validate that each preload entry contains the required keys.
     *
     * @param  array  $preloads
     * @param  bool  $isHot
     * @return void
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    public function ensureValidPreloads(array $preloads, $isHot)
    {
        $urlKey = $isHot ? 'url' : 'file';

        foreach ($preloads as $index => $preload) {
            if (! isset($preload['family'])) {
                throw new ViteException("Font manifest preload entry [{$index}] is missing the [family] key.");
            }

            if (! isset($preload[$urlKey])) {
                throw new ViteException("Font manifest preload entry [{$index}] for family [{$preload['family']}] is missing the [{$urlKey}] key.");
            }
        }
    }

    /**
     * Read and decode a manifest file.
     *
     * @param  string  $path
     * @return array|null
     *
     * @throws \Illuminate\Foundation\ViteException
     */
    protected function readManifest($path)
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
     * Flush cached manifests.
     *
     * @return void
     */
    public function flush()
    {
        static::$manifests = [];
    }
}
