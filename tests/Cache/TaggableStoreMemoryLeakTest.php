<?php

namespace Illuminate\Tests\Cache;

use PHPUnit\Framework\TestCase;


class TaggableStoreMemoryLeakTest extends TestCase
{
    /**
     * Define the acceptable threshold for memory usage increase.
     * In this case, we're setting it to 10 KB.
     */
    const ACCEPTABLE_MEMORY_THRESHOLD = 1024 * 10;

    /**
     * Test the behavior of the flush method on a TaggableStore.
     *
     * This test is designed to identify and confirm a memory leak issue
     * that was reported when using the flush method on a TaggableStore.
     * The test repeatedly flushes the cache and sets new cache entries
     * and monitors memory usage. If memory usage significantly increases,
     * it indicates a potential memory leak.
     *
     * @return void
     */
    public function testMemoryLeakWhenFlushingTaggableStore()
    {
        // Initialize a cache store with tags.
        $store = cache()->store('array')->tags(['test']);

        // Capture the memory usage before the test operations start.
        $memoryBefore = memory_get_usage(true);

        // Repeatedly flush the cache and set new cache entries.
        for ($i = 0; $i < 100000; $i++) {
            $store->flush();
            $key = str_replace('.', '', uniqid());
            $store->set($key, uniqid());

            // For monitoring purposes, print memory usage every 1,000 iterations.
            if ($i % 1000 == 0) {
                echo "Iteration $i: " . memory_get_usage(true) . PHP_EOL;
            }
        }

        // Capture memory usage after the test operations.
        $memoryAfter = memory_get_usage(true);
        $memoryDifference = $memoryAfter - $memoryBefore;

        // Output the total memory difference for inspection.
        echo "Total Memory Difference: $memoryDifference bytes" . PHP_EOL;

        // Assert that the memory usage difference is below the acceptable threshold.
        // If this assertion fails, it indicates a potential memory leak.
        $this->assertTrue($memoryDifference < self::ACCEPTABLE_MEMORY_THRESHOLD);
    }
}
