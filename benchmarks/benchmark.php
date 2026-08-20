<?php

use Illuminate\Support\Benchmark;
use Illuminate\Support\Collection;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;

use function Termwind\render;

require dirname(__DIR__).'/vendor/autoload.php';

function withoutIqrOutliers(Collection $samples): Collection
{
    $samples = $samples->sort()->values();

    $middle = intdiv($samples->count(), 2);

    $lowerQuartile = $samples->take($middle)->median();
    $upperQuartile = $samples->slice($samples->count() - $middle)->median();

    $interquartileRange = $upperQuartile - $lowerQuartile;

    // Tukey's fences exclude values more than 1.5
    // interquartile ranges below Q1 or above Q3.
    $lowerFence = $lowerQuartile - 1.5 * $interquartileRange;
    $upperFence = $upperQuartile + 1.5 * $interquartileRange;

    return $samples->filter(
        fn ($sample) => $sample >= $lowerFence && $sample <= $upperFence,
    );
}

$items = array_fill(0, 1000, [
    'sku' => 'ABC-123',
    'quantity' => 2,
    'price' => 19.99,
    'status' => 'active',
    'email' => 'customer@example.com',
]);

$rules = [
    'items.*.sku' => ['required', 'string', 'max:32'],
    'items.*.quantity' => ['required', 'integer', 'min:1'],
    'items.*.price' => ['required', 'numeric'],
    'items.*.status' => ['required', 'in:active,inactive'],
    'items.*.email' => ['nullable', 'email:filter'],
];

$translator = new Translator(new ArrayLoader, 'en');

$durations = Collection::times(100, function () use ($translator, $items, $rules) {
    $validator = new Validator($translator, ['items' => $items], $rules);

    [$passes, $duration] = Benchmark::value(
        fn () => $validator->passes(),
    );

    if (! $passes) {
        throw new RuntimeException('Validation failed.');
    }

    return $duration;
});

$inliers = withoutIqrOutliers($durations);
$samples = $durations->count();
$included = $inliers->count();
$outliers = $samples - $included;
$average = number_format($inliers->average(), 3).' ms';
$median = number_format($inliers->median(), 3).' ms';
$range = number_format($inliers->min(), 3).' - '.number_format($inliers->max(), 3).' ms';

render(<<<HTML
    <div class="mx-2 my-1">
        <div class="px-1 bg-blue-600 text-white font-bold">Validator benchmark</div>
        <div class="mt-1"><span class="w-20 text-gray">Samples</span> {$samples}</div>
        <div><span class="w-20 text-gray">Included</span> {$included}</div>
        <div><span class="w-20 text-gray">Outliers</span> <span class="text-yellow">{$outliers}</span></div>
        <div class="mt-1"><span class="w-20 text-gray">Average</span> <span class="text-green font-bold">{$average}</span></div>
        <div><span class="w-20 text-gray">Median</span> {$median}</div>
        <div><span class="w-20 text-gray">Range</span> {$range}</div>
    </div>
HTML);
