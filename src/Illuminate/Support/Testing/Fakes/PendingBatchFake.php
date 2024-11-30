<?php

namespace Illuminate\Support\Testing\Fakes;

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;
use Throwable;
use Closure;
use Illuminate\Support\Number;

class PendingBatchFake extends PendingBatch
{
    /**
     * The fake bus instance.
     *
     * @var \Illuminate\Support\Testing\Fakes\BusFake
     */
    protected $bus;

    /**
     * The jobs that have been checked.
     *
     * @var array
     */
    protected $expected = [];

    /**
     * Create a new pending batch instance.
     *
     * @param  \Illuminate\Support\Testing\Fakes\BusFake  $bus
     * @param  \Illuminate\Support\Collection  $jobs
     * @return void
     */
    public function __construct(BusFake $bus, Collection $jobs)
    {
        $this->bus = $bus;
        $this->jobs = $jobs;
    }

    /**
     * Dispatch the batch.
     *
     * @return \Illuminate\Bus\Batch
     */
    public function dispatch()
    {
        return $this->bus->recordPendingBatch($this);
    }

    /**
     * Dispatch the batch after the response is sent to the browser.
     *
     * @return \Illuminate\Bus\Batch
     */
    public function dispatchAfterResponse()
    {
        return $this->bus->recordPendingBatch($this);
    }

    /**
     * Assert that the batch contains a job of the given type.
     *
     * @param  string|int  $expectedJob
     * @param  array  $expectedParameters
     * @return $this
     */
    public function has(string|int $expectedJob, array $expectedParameters = [])
    {
        if (is_int($expectedJob)) {
            PHPUnit::assertCount(
                $expectedJob,
                $this->jobs,
                "Failed to assert the batch contains the exact number of [{$expectedJob}] jobs."
            );

            return $this;
        }

        PHPUnit::assertTrue(
            $this->jobs->contains(fn ($job) =>
                get_class($job) === $expectedJob &&
                    $this->parametersMatch($job, $expectedJob, $expectedParameters)
            ),
            "The batch does not contain a job of type [{$expectedJob}]."
        );

        array_push($this->expected, $expectedJob);

        return $this;
    }

    /**
     * Assert that the batch does not contain a job of the given type.
     *
     * @param  string  $unexpectedJob
     * @return $this
     */
    public function missing(string $unexpectedJob)
    {
        PHPUnit::assertFalse(
            $this->jobs->contains(function ($job) use ($unexpectedJob) {
                return get_class($job) === $unexpectedJob;
            }),
            "The batch does not miss a job of type [{$unexpectedJob}]."
        );

        return $this;
    }

    /**
     * Assert that the batch contains all of the given jobs.
     *
     * @param  array  $expectedJobs
     * @return $this
     */
    public function hasAll(array $expectedJobs)
    {
        try {
            foreach ($expectedJobs as $expectedJob) {
                $this->has($expectedJob);
            }
        } catch (Throwable $e) {
            throw new $e('The batch does not contain all expected jobs.');
        }

        return $this;
    }

    /**
     * Assert that the batch does not contain any of the given jobs.
     *
     * @param  array  $unexpectedJobs
     * @return $this
     */
    public function missingAll(array $unexpectedJobs)
    {
        try {
            foreach ($unexpectedJobs as $unexpectedJob) {
                $this->missing($unexpectedJob);
            }
        } catch (Throwable $e) {
            throw new $e('The batch does not miss all of given jobs.');
        }

        return $this;
    }

    /**
     * Assert that the batch contains any of the given jobs.
     *
     * @param  array  $expectedJobs
     * @return $this
     */
    public function hasAny(...$expectedJobs)
    {
        PHPUnit::assertTrue(
            $this->jobs->contains(function ($job) use ($expectedJobs) {
                return in_array(get_class($job), $expectedJobs);
            }),
            "The batch does not contains any of the expected jobs."
        );

        array_push($this->expected, ...$expectedJobs);

        return $this;
    }

    /**
     * Assert that the first job in the batch matches the given callback.
     *
     * @param  Closure  $callback
     * @return $this
     */
    public function first(Closure $callback)
    {
        $firstJob = $this->jobs->first();

        PHPUnit::assertNotNull($firstJob, "Failed to assert the batch contains any jobs.");

        try {
            $callback(
                new self(
                    $this->bus,
                    is_array($firstJob) ? collect($firstJob) : collect([$firstJob])
                )
            );
        } catch (Throwable $e) {
            throw new $e('The first one in the batch does not matches the given callback because of: ' . $e->getMessage());
        }

        array_push($this->expected, is_array($firstJob) ?
            array_map(fn ($job) => get_class($job), $firstJob) :
            get_class($firstJob)
        );

        return $this;
    }

    /**
     * Assert that the nth job in the batch matches the given callback.
     *
     * @param  int  $index
     * @param  Closure  $callback
     * @param  array  $parameters
     * @return $this
     */
    public function nth(int $index, Closure|string $callback, array $parameters = [])
    {
        $nthJob = $this->jobs->slice($index, 1)->first();

        PHPUnit::assertNotNull($nthJob, "The batch does not contains a job at index [{$index}].");

        // If the callback is a Closure, we will assume the nthJob will matches the callback.
        if (func_num_args() == 2) {
            try {
                $callback(
                    new self(
                        $this->bus,
                        is_array($nthJob) ? collect($nthJob) : collect([$nthJob])
                    )
                );
            } catch (Throwable $e) {
                throw new $e(sprintf(
                    'The [%s] one in the batch does not matches the given callback because of: %s',
                    Number::ordinal($index, 'en'),
                    $e->getMessage()
                ));
            }

            array_push($this->expected, is_array($nthJob) ?
                array_map(fn ($job) => get_class($job), $nthJob) :
                get_class($nthJob)
            );
        }

        // If the callback is a string, we will assume the nthJob will matches the given type and parameters.
        if (func_num_args() == 3) {
            PHPUnit::assertTrue(
                get_class($nthJob) === $callback &&
                    $this->parametersMatch($nthJob, $callback, $parameters),
                "The batch does not contain a job of type [{$callback}] at index [{$index}]."
            );

            array_push($this->expected, $callback);
        }

        return $this;
    }

    /**
     * Assert that the batch contains exactly the given jobs with the specified parameters.
     *
     * @param  array  $expectedJobs
     * @return $this
     */
    public function equal(array $expectedJobs)
    {
        $this->jobs->each(function ($actualJob, $nth) use (&$expectedJobs) {
            if (is_array($actualJob)) {
                PHPUnit::assertTrue(
                    is_array(current($expectedJobs)),
                    "Failed to assert that the [{$nth}]th job in the batch is an array."
                );

                foreach ($actualJob as $nestedJob) {
                    $jobClass = get_class($nestedJob);
                    $this->parametersMatch($nestedJob, $jobClass, current($expectedJobs)[$jobClass] ?? []);
                }

                next($expectedJobs);

                return true;
            }

            $jobClass = get_class($actualJob);
            $this->parametersMatch($actualJob, $jobClass, $expectedJobs[$jobClass] ?? []);
            next($expectedJobs);
        });

        return $this;
    }

    /**
     * Assert that the batch has unexpected jobs beyond those checked.
     *
     * @return $this
     */
    public function etc()
    {
        $expectedJobs = array_map('serialize', $this->expected);

        $actualJobs = $this->jobs->map(
            fn($job) => serialize(is_array($job) ?
                array_map(fn($j) => get_class($j), $job) :
                get_class($job)
            )
        )->toArray();

        PHPUnit::assertNotEmpty(
            array_diff($actualJobs, $expectedJobs),
            'Failed to assert that there are unexpected jobs in the batch.'
        );

        return $this;
    }

    /**
     * Assert that the batch does not contain any of the given jobs.
     *
     * @param  mixed  $actual
     * @param  string  $expectedClass
     * @param  array  $expectedParameters
     * @return bool
     */
    protected function parametersMatch($actual, string $expectedClass, array $expectedParameters)
    {
        PHPUnit::assertEquals(
            new $expectedClass(...$expectedParameters),
            $actual,
            "The job parameters does not match the expected values for class [{$expectedClass}]."
        );

        return true;
    }
}
