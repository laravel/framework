<?php

namespace Illuminate\Http\Client;

use Illuminate\Support\Str;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\VarDumper\VarDumper;

class HttpClientCurlBuilder
{
    /**
     * The underlying PSR-7 request instance.
     *
     * @var  \Psr\Http\Message\RequestInterface
     */
    protected $request;

    /**
     * The individual components that make up the cURL command.
     *
     * @var  array|string[]
     */
    protected $parts = [];

    /**
     * Determines whether to format the cURL command for readability.
     *
     * @var  bool
     */
    protected $pretty = false;

    /**
     * Create a new Http Client Curl builder instance.
     *
     * @param  \Psr\Http\Message\RequestInterface  $request
     */
    private function __construct(RequestInterface $request)
    {
        $this->request = $request;

        $this->parts [] = 'curl';
    }

    /**
     * Create a new builder instance for the given request.
     *
     * @param  RequestInterface  $request
     * @return $this
     */
    public static function forRequest(RequestInterface $request)
    {
        return new self($request);
    }

    /**
     * Specify the format of the cURL command for readability.
     *
     * @param  bool  $prettyFormat
     * @return $this
     */
    public function pretty(bool $prettyFormat = true)
    {
        $this->pretty = $prettyFormat;

        return $this;
    }

    /**
     * Build and return the cURL command.
     *
     * @return string
     */
    public function build()
    {
        return $this
            ->addMethod()
            ->addUrl()
            ->addHeaders()
            ->addBody()
            ->generate();
    }

    /**
     * Build and dump the curl command and end the script
     *
     * @return never
     */
    public function dd()
    {
        VarDumper::dump($this->build());

        exit(1);
    }

    /**
     * Add the HTTP request method to the cURL command.
     *
     * @return $this
     */
    private function addMethod()
    {
        $this->parts[] = '--request '.escapeshellarg($this->request->getMethod());

        return $this;
    }

    /**
     * Add the URL to the cURL command.
     *
     * @return $this
     */
    private function addUrl()
    {
        $this->parts[] = '--url '.escapeshellarg((string) $this->request->getUri());

        return $this;
    }

    /**
     * Add all relevant headers to the cURL command.
     *
     * @return $this
     */
    private function addHeaders()
    {
        foreach ($this->request->getHeaders() as $name => $values) {
            $headerName = Str::lower($name);

            if (in_array($headerName, ['content-length', 'host'])) {
                continue;
            }

            if ($headerName === 'content-type'
                && str_starts_with($this->request->getHeaderLine($name), 'multipart/form-data')) {
                $this->parts[] = '--header '.escapeshellarg('content-type: multipart/form-data');

                continue;
            }

            $headerLine = $name.': '.implode(', ', $values);
            $this->parts[] = '--header '.escapeshellarg($headerLine);
        }

        return $this;
    }

    /**
     * Add the request body to the cURL command.
     *
     * @return $this
     */
    private function addBody()
    {
        $bodyContent = $this->request->getBody()->getContents();
        $contentType = $this->request->getHeaderLine('Content-Type');

        if (str_contains($contentType, 'multipart/form-data')) {
            $this->parts = array_merge($this->parts, $this->buildMultipartParts($contentType, $bodyContent));
        } elseif ($bodyContent !== '' && $bodyContent !== '0') {
            $this->parts[] = "--data ".escapeshellarg($bodyContent);
        }

        return $this;
    }

    /**
     * Parse and build multipart form data parts for the cURL command.
     *
     * @param  string  $contentType
     * @param  string  $body
     * @return array
     */
    private function buildMultipartParts(string $contentType, string $body)
    {
        $formParts = [];

        if (in_array(preg_match('/boundary=(.*)$/', $contentType, $matches), [0, false], true)) {
            foreach (json_decode($body, true) as $name => $value) {
                $formParts [] = '--form '.escapeshellarg("$name=$value");
            }

            return $formParts;
        }

        $boundary = $matches[1];
        $sections = explode("--$boundary", $body);

        foreach ($sections as $section) {
            $section = trim($section);

            if ($section === '') {
                continue;
            }

            if ($section === '--') {
                continue;
            }

            $this->parseMultipartSection($section, $formParts);
        }

        return $formParts;
    }

    /**
     * Parse an individual multipart form data section.
     *
     * @param  string  $section
     * @param  array  $formParts
     * @return void
     */
    private function parseMultipartSection(string $section, array &$formParts)
    {
        if (in_array(preg_match('/name="([^"]+)"/', $section, $nameMatches), [0, false], true)) {
            return;
        }

        $name = $nameMatches[1];

        if (preg_match('/filename="([^"]+)"/', $section, $fileMatches)) {
            $filename = $fileMatches[1];
            $formParts[] = '--form '.escapeshellarg("$name=@$filename");
        } elseif (preg_match('/\r\n\r\n(.*?)(\r\n--|$)/s', $section, $valueMatches)) {
            $value = trim($valueMatches[1]);
            $formParts[] = '--form '.escapeshellarg("$name=$value");
        }
    }

    /**
     * Generate the final cURL command string.
     *
     * @return string
     */
    private function generate()
    {
        return $this->pretty
            ? $this->buildPretty()
            : $this->buildInline();
    }

    /**
     * Format the cURL command with newlines and indentation for readability.
     *
     * @return string
     */
    private function buildPretty()
    {
        $command = array_shift($this->parts);

        foreach ($this->parts as $part) {
            $command .= " \\\n  ".$part;
        }

        return $command;
    }

    /**
     * Format the cURL command as a single line string.
     *
     * @return string
     */
    private function buildInline()
    {
        return implode(' ', $this->parts);
    }
}
