<?php

namespace Illuminate\Foundation\Console;

use ParseError;
use PhpToken;

class ConfigFileMerger
{
    /**
     * The tokens of the file currently being parsed.
     *
     * @var \PhpToken[]
     */
    protected $tokens = [];

    /**
     * The source of the file currently being parsed.
     *
     * @var string
     */
    protected $source = '';

    /**
     * Merge the keys missing from the existing configuration file in from the incoming one.
     *
     * The files are never evaluated. Missing entries are copied from the incoming source
     * (comments included) so the existing file's values, comments and formatting are
     * preserved. Returns null when either file is not a plain "return [...]" file.
     *
     * @param  string  $existing
     * @param  string  $incoming
     * @return string|null
     */
    public function merge(string $existing, string $incoming)
    {
        $existingFile = $this->parse($existing);
        $incomingFile = $this->parse($incoming);

        if (is_null($existingFile) || is_null($incomingFile)) {
            return null;
        }

        $eol = str_contains($existing, "\r\n") ? "\r\n" : "\n";

        $insertions = [];

        $this->mergeArrays(
            $existingFile['array'], $incomingFile['array'], $incoming, $eol, $insertions,
        );

        if ($insertions === []) {
            return $existing;
        }

        $this->insertImports($existingFile, $incomingFile, $insertions, $eol);

        usort($insertions, fn ($a, $b) => $b[0] <=> $a[0]);

        $merged = $existing;

        foreach ($insertions as [$offset, $text]) {
            $merged = substr_replace($merged, $text, $offset, 0);
        }

        try {
            PhpToken::tokenize($merged, TOKEN_PARSE);
        } catch (ParseError) {
            return null;
        }

        return $merged;
    }

    /**
     * Queue the insertion of incoming "use" imports that the inserted entries reference.
     *
     * @param  array  $existing
     * @param  array  $incoming
     * @param  array  $insertions
     * @param  string  $eol
     * @return void
     */
    protected function insertImports(array $existing, array $incoming, array &$insertions, string $eol)
    {
        $insertedText = implode("\n", array_column($insertions, 1));

        $existingImports = array_map(
            fn ($import) => $this->normalizeImport($import['text']), $existing['imports']
        );

        $imports = [];

        foreach ($incoming['imports'] as $import) {
            if (in_array($this->normalizeImport($import['text']), $existingImports, true)) {
                continue;
            }

            foreach ($this->importedNames($import['text']) as $name) {
                if (preg_match('/(?<![\\\\\w$])'.preg_quote($name, '/').'\b/', $insertedText)) {
                    $imports[] = $import['text'];

                    break;
                }
            }
        }

        if ($imports === []) {
            return;
        }

        $insertions[] = $existing['imports'] === []
            ? [$existing['returnPos'], implode($eol, $imports).$eol.$eol]
            : [end($existing['imports'])['end'], $eol.implode($eol, $imports)];
    }

    /**
     * Normalize the whitespace of the given "use" statement for comparison.
     *
     * @param  string  $import
     * @return string
     */
    protected function normalizeImport(string $import)
    {
        return preg_replace('/\s+/', ' ', $import);
    }

    /**
     * Get the names a "use" statement makes available.
     *
     * @param  string  $import
     * @return array
     */
    protected function importedNames(string $import)
    {
        $import = preg_replace('/^use\s+(function\s+|const\s+)?|\s*;$/i', '', trim($import));

        if (preg_match('/\{(.*)\}/s', $import, $matches)) {
            $import = $matches[1];
        }

        return array_map(function ($item) {
            $item = trim($item);

            return preg_match('/\sas\s+(\w+)$/i', $item, $matches)
                ? $matches[1]
                : ltrim(strrchr('\\'.$item, '\\'), '\\');
        }, array_filter(explode(',', $import), fn ($item) => trim($item) !== ''));
    }

    /**
     * Queue the insertions needed to add the incoming array's missing keys to the existing array.
     *
     * @param  array  $existing
     * @param  array  $incoming
     * @param  string  $incomingSource
     * @param  string  $eol
     * @param  array  $insertions
     * @return void
     */
    protected function mergeArrays(array $existing, array $incoming, string $incomingSource, string $eol, array &$insertions)
    {
        if ($existing['dynamicKeys'] ||
            ! $this->isAssociative($incoming, false) ||
            ! $this->isAssociative($existing, true)) {
            return;
        }

        $existingEntries = [];

        foreach ($existing['entries'] as $entry) {
            $existingEntries[$entry['key']] ??= $entry;
        }

        $missing = [];

        foreach ($incoming['entries'] as $entry) {
            if (! array_key_exists($entry['key'], $existingEntries)) {
                $missing[$entry['key']] ??= $entry;
            } elseif ($existingEntries[$entry['key']]['array'] && $entry['array']) {
                $this->mergeArrays(
                    $existingEntries[$entry['key']]['array'], $entry['array'], $incomingSource, $eol, $insertions
                );
            }
        }

        if ($missing !== []) {
            $this->insertEntries($existing, $incoming, array_values($missing), $incomingSource, $eol, $insertions);
        }
    }

    /**
     * Queue the insertions that append the given incoming entries to the existing array.
     *
     * @param  array  $existing
     * @param  array  $incoming
     * @param  array  $missing
     * @param  string  $incomingSource
     * @param  string  $eol
     * @param  array  $insertions
     * @return void
     */
    protected function insertEntries(array $existing, array $incoming, array $missing, string $incomingSource, string $eol, array &$insertions)
    {
        $indent = $existing['lineIndent'].$this->indentUnit($incoming);

        // Single line arrays such as ['a' => 1] simply get the new entries appended inline...
        if (! $existing['multiline'] && $existing['entries'] !== []) {
            $text = '';

            foreach ($missing as $entry) {
                $text .= ', '.$this->entryText($entry, $incomingSource, '', $eol);
            }

            $insertions[] = [end($existing['entries'])['valueEnd'], $text];

            return;
        }

        $separator = str_repeat($eol, $this->entrySeparatorNewlines($existing, $incoming));

        $pieces = [];

        foreach ($missing as $entry) {
            $pieces[] = $this->entryText($entry, $incomingSource, $indent, $eol).','.
                (is_null($entry['comment']) ? '' : ' '.$entry['comment']);
        }

        if ($existing['entries'] === []) {
            $text = $eol.$indent.implode($separator.$indent, $pieces);

            if (! $existing['multiline']) {
                $text .= $eol.$existing['lineIndent'];
            }

            $insertions[] = [$existing['open'], $text];

            return;
        }

        $last = end($existing['entries']);

        $indent = $last['indent'] ?? $indent;

        $text = $separator.$indent.implode($separator.$indent, $pieces);

        if (! $last['hasComma']) {
            if ($last['end'] === $last['valueEnd']) {
                $text = ','.$text;
            } else {
                $insertions[] = [$last['valueEnd'], ','];
            }
        }

        $insertions[] = [$last['end'], $text];
    }

    /**
     * Get the source of the given entry, re-indented for its new location.
     *
     * @param  array  $entry
     * @param  string  $source
     * @param  string  $indent
     * @param  string  $eol
     * @return string
     */
    protected function entryText(array $entry, string $source, string $indent, string $eol)
    {
        $lines = explode("\n", str_replace("\r\n", "\n", substr(
            $source, $entry['start'], $entry['valueEnd'] - $entry['start']
        )));

        $from = $entry['indent'] ?? '';

        foreach ($lines as $index => $line) {
            if ($index === 0) {
                continue;
            }

            if (trim($line) === '') {
                $lines[$index] = '';
            } elseif ($from !== '' && str_starts_with($line, $from)) {
                $lines[$index] = $indent.substr($line, strlen($from));
            }
        }

        return implode($eol, $lines);
    }

    /**
     * Determine the indentation used for an array's entries relative to the array itself.
     *
     * @param  array  $array
     * @return string
     */
    protected function indentUnit(array $array)
    {
        $indent = $array['entries'][0]['indent'] ?? null;

        if (! is_null($indent) &&
            strlen($indent) > strlen($array['lineIndent']) &&
            str_starts_with($indent, $array['lineIndent'])) {
            return substr($indent, strlen($array['lineIndent']));
        }

        return '    ';
    }

    /**
     * Determine how many newlines should separate inserted entries (2 means a blank line).
     *
     * @param  array  $existing
     * @param  array  $incoming
     * @return int
     */
    protected function entrySeparatorNewlines(array $existing, array $incoming)
    {
        foreach ([$existing, $incoming] as $array) {
            if (count($array['entries']) >= 2) {
                return max(1, min(2, end($array['entries'])['gap']));
            }
        }

        return 1;
    }

    /**
     * Determine if every entry of the given array has a literal string key.
     *
     * @param  array  $array
     * @param  bool  $allowEmpty
     * @return bool
     */
    protected function isAssociative(array $array, bool $allowEmpty)
    {
        if ($array['entries'] === []) {
            return $allowEmpty;
        }

        foreach ($array['entries'] as $entry) {
            if (is_null($entry['key'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Parse the array returned by the given configuration file source.
     *
     * @param  string  $source
     * @return array|null
     */
    protected function parse(string $source)
    {
        try {
            $this->tokens = PhpToken::tokenize($source, TOKEN_PARSE);
        } catch (ParseError) {
            return null;
        }

        $this->source = $source;

        try {
            return $this->parseFile();
        } finally {
            $this->tokens = [];
            $this->source = '';
        }
    }

    /**
     * Parse the current tokens as a file consisting of a single returned array.
     *
     * @return array{array: array, imports: array, returnPos: int}|null
     */
    protected function parseFile()
    {
        if (! $this->token(0)?->is(T_OPEN_TAG)) {
            return null;
        }

        $index = $this->skipTrivia(1);

        $imports = [];

        while ($this->token($index)?->is([T_DECLARE, T_USE, T_NAMESPACE])) {
            $start = $index;

            while ($this->token($index) && ! $this->token($index)->is(';')) {
                $index++;
            }

            if (! $this->token($index)) {
                return null;
            }

            if ($this->tokens[$start]->is(T_USE)) {
                $imports[] = [
                    'text' => substr($this->source, $this->tokens[$start]->pos, $this->endOf($index) - $this->tokens[$start]->pos),
                    'end' => $this->endOf($index),
                ];
            }

            $index = $this->skipTrivia($index + 1);
        }

        if (! $this->token($index)?->is(T_RETURN)) {
            return null;
        }

        $returnPos = $this->tokens[$index]->pos;

        $array = $this->parseArray($this->skipTrivia($index + 1));

        if (is_null($array)) {
            return null;
        }

        $index = $this->skipTrivia($array['closeIndex'] + 1);

        if (! $this->token($index)?->is(';')) {
            return null;
        }

        for ($index++; $token = $this->token($index); $index++) {
            if (! $this->isTrivia($token) &&
                ! $token->is(T_CLOSE_TAG) &&
                ! ($token->is(T_INLINE_HTML) && trim($token->text) === '')) {
                return null;
            }
        }

        return ['array' => $array, 'imports' => $imports, 'returnPos' => $returnPos];
    }

    /**
     * Parse the array literal starting at the given token index.
     *
     * @param  int  $index
     * @return array|null
     */
    protected function parseArray(int $index)
    {
        $start = $this->token($index);

        if ($start?->is('[')) {
            $closer = ']';
        } elseif ($start?->is(T_ARRAY)) {
            $index = $this->skipTrivia($index + 1);

            if (! $this->token($index)?->is('(')) {
                return null;
            }

            $closer = ')';
        } else {
            return null;
        }

        $array = [
            'open' => $this->endOf($index),
            'lineIndent' => $this->lineIndent($start->pos),
            'entries' => [],
            'dynamicKeys' => false,
        ];

        $previousEnd = $array['open'];
        $index++;

        while (true) {
            $first = $this->skipTrivia($index);

            if (! $this->token($first)) {
                return null;
            }

            // Comments on the same line as the previous entry belong to it, anything after
            // the next line break is considered a leading comment of the following entry...
            $blockStart = null;
            $sawNewline = false;

            for ($i = $index; $i < $first; $i++) {
                $token = $this->tokens[$i];

                if ($token->is(T_WHITESPACE)) {
                    $sawNewline = $sawNewline || str_contains($token->text, "\n");
                } elseif (! $sawNewline) {
                    if ($array['entries'] !== []) {
                        $last = array_key_last($array['entries']);

                        $array['entries'][$last]['comment'] = $token->text;
                        $array['entries'][$last]['end'] = $previousEnd = $this->endOf($i);
                    }
                } else {
                    $blockStart ??= $token->pos;
                }
            }

            if ($this->tokens[$first]->is($closer)) {
                break;
            }

            $blockStart ??= $this->tokens[$first]->pos;

            $end = $this->findEntryEnd($first, $closer);

            if (is_null($end)) {
                return null;
            }

            $last = $this->previousSignificant($end);

            [$key, $valueFirst] = [null, $first];

            if ($this->tokens[$first]->is(T_CONSTANT_ENCAPSED_STRING)) {
                $arrow = $this->skipTrivia($first + 1);

                if ($this->tokens[$arrow]->is(T_DOUBLE_ARROW)) {
                    $key = $this->unquote($this->tokens[$first]->text);
                    $valueFirst = $this->skipTrivia($arrow + 1);
                }
            }

            if (is_null($key) && $this->hasTopLevelArrow($first, $end)) {
                $array['dynamicKeys'] = true;
            }

            $nested = null;

            if (! is_null($key) && $this->tokens[$valueFirst]->is(['[', T_ARRAY])) {
                $nested = $this->parseArray($valueFirst);

                if (! is_null($nested) && $nested['closeIndex'] !== $last) {
                    $nested = null;
                }
            }

            $hasComma = $this->tokens[$end]->is(',');

            $array['entries'][] = [
                'key' => $key,
                'start' => $blockStart,
                'indent' => $this->indentAt($blockStart),
                'gap' => substr_count(substr($this->source, $previousEnd, $blockStart - $previousEnd), "\n"),
                'valueEnd' => $this->endOf($last),
                'end' => $previousEnd = $hasComma ? $this->endOf($end) : $this->endOf($last),
                'hasComma' => $hasComma,
                'comment' => null,
                'array' => $nested,
            ];

            $index = $hasComma ? $end + 1 : $last + 1;
        }

        $array['closeIndex'] = $first;
        $array['multiline'] = str_contains(
            substr($this->source, $array['open'], $this->tokens[$first]->pos - $array['open']), "\n"
        );

        return $array;
    }

    /**
     * Find the index of the comma or closing bracket that ends the entry starting at the given index.
     *
     * @param  int  $index
     * @param  string  $closer
     * @return int|null
     */
    protected function findEntryEnd(int $index, string $closer)
    {
        $depth = 0;

        for (; $token = $this->token($index); $index++) {
            if ($this->isOpener($token)) {
                $depth++;
            } elseif ($token->is([']', ')', '}'])) {
                if ($depth === 0) {
                    return $token->is($closer) ? $index : null;
                }

                $depth--;
            } elseif ($depth === 0 && $token->is(',')) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Determine if the entry between the given indexes has a top-level "=>" (a non-literal key).
     *
     * @param  int  $index
     * @param  int  $end
     * @return bool
     */
    protected function hasTopLevelArrow(int $index, int $end)
    {
        $depth = 0;

        for (; $index < $end; $index++) {
            $token = $this->tokens[$index];

            if ($this->isOpener($token)) {
                $depth++;
            } elseif ($token->is([']', ')', '}'])) {
                $depth--;
            } elseif ($depth === 0 && $token->is([T_FN, T_FUNCTION, T_STATIC])) {
                return false;
            } elseif ($depth === 0 && $token->is(T_DOUBLE_ARROW)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the given token opens a nested bracket pair.
     *
     * @param  \PhpToken  $token
     * @return bool
     */
    protected function isOpener(PhpToken $token)
    {
        return $token->is(['[', '(', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES, T_ATTRIBUTE]);
    }

    /**
     * Determine if the given token is whitespace or a comment.
     *
     * @param  \PhpToken  $token
     * @return bool
     */
    protected function isTrivia(PhpToken $token)
    {
        return $token->is([T_WHITESPACE, T_COMMENT, T_DOC_COMMENT]);
    }

    /**
     * Get the index of the first non-trivia token at or after the given index.
     *
     * @param  int  $index
     * @return int
     */
    protected function skipTrivia(int $index)
    {
        while (($token = $this->token($index)) && $this->isTrivia($token)) {
            $index++;
        }

        return $index;
    }

    /**
     * Get the index of the last non-trivia token before the given index.
     *
     * @param  int  $index
     * @return int
     */
    protected function previousSignificant(int $index)
    {
        do {
            $index--;
        } while ($index > 0 && $this->isTrivia($this->tokens[$index]));

        return $index;
    }

    /**
     * Get the token at the given index.
     *
     * @param  int  $index
     * @return \PhpToken|null
     */
    protected function token(int $index)
    {
        return $this->tokens[$index] ?? null;
    }

    /**
     * Get the byte offset just after the token at the given index.
     *
     * @param  int  $index
     * @return int
     */
    protected function endOf(int $index)
    {
        return $this->tokens[$index]->pos + strlen($this->tokens[$index]->text);
    }

    /**
     * Get the indentation of the line containing the given offset.
     *
     * @param  int  $offset
     * @return string
     */
    protected function lineIndent(int $offset)
    {
        preg_match('/^[ \t]*/', substr($this->source, $this->lineStart($offset)), $matches);

        return $matches[0];
    }

    /**
     * Get the indentation before the given offset if it is the first thing on its line.
     *
     * @param  int  $offset
     * @return string|null
     */
    protected function indentAt(int $offset)
    {
        $lineStart = $this->lineStart($offset);

        $prefix = substr($this->source, $lineStart, $offset - $lineStart);

        return preg_match('/^[ \t]*$/', $prefix) ? $prefix : null;
    }

    /**
     * Get the offset of the start of the line containing the given offset.
     *
     * @param  int  $offset
     * @return int
     */
    protected function lineStart(int $offset)
    {
        $newline = strrpos(substr($this->source, 0, $offset), "\n");

        return $newline === false ? 0 : $newline + 1;
    }

    /**
     * Get the value of the given string literal.
     *
     * @param  string  $literal
     * @return string
     */
    protected function unquote(string $literal)
    {
        $value = substr($literal, 1, -1);

        return $literal[0] === "'"
            ? str_replace(['\\\\', "\\'"], ['\\', "'"], $value)
            : stripcslashes($value);
    }
}
