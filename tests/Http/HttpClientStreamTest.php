<?php

namespace Illuminate\Tests\Http;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase;

class HttpClientStreamTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $messages = [
            "Streamed Message 1\n",
            "Streamed Message 2\n",
            "Streamed Message 3\n",
            "Streamed Message 4\n",
            'Streamed Message 5',
        ];

        Http::fake(['http://example.test/stream' => fn () => Http::response(implode($messages))]);
    }

    public function testStreamYieldsOneChunkPerMessageWhenChunkSizeMatchesMessageSize()
    {
        $stream = Http::withOptions(['stream' => true])
            ->get('http://example.test/stream')
            ->stream(chuckLength: 19);

        $expectedMessages = [
            "Streamed Message 1\n",
            "Streamed Message 2\n",
            "Streamed Message 3\n",
            "Streamed Message 4\n",
            'Streamed Message 5',
        ];

        $chunkIndex = 0;
        foreach ($stream as $chunk) {
            $this->assertSame($expectedMessages[$chunkIndex], $chunk);
            $chunkIndex++;
        }

        $this->assertSame(count($expectedMessages), $chunkIndex);
    }

    public function testStreamYieldsChunksAcrossMessageBoundariesWhenChunkSizeIsSmaller()
    {
        $stream = Http::withOptions(['stream' => true])
            ->get('http://example.test/stream')
            ->stream(chuckLength: 10);

        $expectedMessages = [
            'Streamed M',
            "essage 1\nS",
            'treamed Me',
            "ssage 2\nSt",
            'reamed Mes',
            "sage 3\nStr",
            'eamed Mess',
            "age 4\nStre",
            'amed Messa',
            'ge 5',
        ];

        $chunkIndex = 0;
        foreach ($stream as $chunk) {
            $this->assertSame($expectedMessages[$chunkIndex], $chunk);
            $chunkIndex++;
        }

        $this->assertSame(count($expectedMessages), $chunkIndex);
    }

    public function testStreamLinesYieldsCompleteLinesRegardlessOfChunkSize()
    {
        $streams = [
            Http::withOptions(['stream' => true])
                ->get('http://example.test/stream')
                ->streamLines(), // reads single bytes
            Http::withOptions(['stream' => true])
                ->get('http://example.test/stream')
                ->streamLines(chunkLength: 17), // chunk length smaller than line length
            Http::withOptions(['stream' => true])
                ->get('http://example.test/stream')
                ->streamLines(chunkLength: 27), // chunk length larger than line length
        ];

        $expectedMessages = [
            'Streamed Message 1',
            'Streamed Message 2',
            'Streamed Message 3',
            'Streamed Message 4',
            'Streamed Message 5',
        ];

        foreach ($streams as $stream) {
            $chunkIndex = 0;
            foreach ($stream as $chunk) {
                $this->assertSame($expectedMessages[$chunkIndex], $chunk);
                $chunkIndex++;
            }

            $this->assertSame(count($expectedMessages), $chunkIndex);
        }
    }

    public function testStreamLinesSupportsCustomSeparators()
    {
        // Test with pipe separator
        Http::fake(['http://example.test/pipe' => fn () => Http::response('Line 1|Line 2|Line 3')]);

        $pipeStream = Http::withOptions(['stream' => true])
            ->get('http://example.test/pipe')
            ->streamLines(separator: '|');

        $expectedPipeMessages = ['Line 1', 'Line 2', 'Line 3'];
        foreach ($pipeStream as $i => $chunk) {
            $this->assertSame($expectedPipeMessages[$i], $chunk);
        }

        // Test with comma separator
        Http::fake(['http://example.test/comma' => fn () => Http::response('Item1,Item2,Item3')]);

        $commaStream = Http::withOptions(['stream' => true])
            ->get('http://example.test/comma')
            ->streamLines(separator: ',');

        $expectedCommaMessages = ['Item1', 'Item2', 'Item3'];
        foreach ($commaStream as $i => $chunk) {
            $this->assertSame($expectedCommaMessages[$i], $chunk);
        }

        // Test with multi-character separator
        Http::fake(['http://example.test/multi' => fn () => Http::response('First||Second||Third')]);

        $multiStream = Http::withOptions(['stream' => true])
            ->get('http://example.test/multi')
            ->streamLines(separator: '||');

        $expectedMultiMessages = ['First', 'Second', 'Third'];
        foreach ($multiStream as $i => $chunk) {
            $this->assertSame($expectedMultiMessages[$i], $chunk);
        }
    }
}
