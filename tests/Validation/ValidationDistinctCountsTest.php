<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Concerns\ValidatesAttributes;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ValidationDistinctCountsTest extends TestCase
{
    #[DataProvider('distinctValues')]
    public function test_distinct_comparison_semantics_are_preserved(array $values, string $rule, array $failed): void
    {
        $validator = $this->validator(['values' => $values], ['values.*' => $rule]);

        $this->assertSame($failed === [], $validator->passes());
        $this->assertSame(array_map(fn ($index) => 'values.'.$index, $failed), array_keys($validator->failed()));
    }

    public static function distinctValues(): array
    {
        return [
            'unique ASCII' => [['alpha', 'BETA', 'gamma'], 'distinct:ignore_case', []],
            'duplicate ASCII' => [['alpha', 'ALPHA', 'beta'], 'distinct:ignore_case', [0, 1]],
            'numeric strings' => [['1', '01', '1.0', '-0', '0', '+0'], 'distinct:ignore_case', []],
            'regex punctuation' => [['a.b', 'A.B', 'a/b', 'A/B'], 'distinct:ignore_case', [0, 1, 2, 3]],
            'Unicode case' => [['é', 'É'], 'distinct:ignore_case', [0, 1]],
            'Kelvin sign' => [['K', "\u{212A}"], 'distinct:ignore_case', [0, 1]],
            'long s' => [['s', "\u{17F}"], 'distinct:ignore_case', [0, 1]],
            'Greek sigma' => [['Σ', 'σ', 'ς'], 'distinct:ignore_case', [0, 1, 2]],
            'final newline' => [['a', "a\n"], 'distinct:ignore_case', [0]],
            'reversed final newline' => [["a\n", 'a'], 'distinct:ignore_case', [1]],
            'two final newlines' => [["a\n", "a\n\n"], 'distinct:ignore_case', [0]],
            'carriage return' => [['a', "a\r"], 'distinct:ignore_case', []],
            'tab' => [["a\t", "A\t"], 'distinct:ignore_case', [0, 1]],
            'NUL' => [["a\0", "A\0"], 'distinct:ignore_case', [0, 1]],
            'invalid UTF-8' => [["caf\xE9", 'bar'], 'distinct:ignore_case', []],
            'duplicate invalid UTF-8' => [["caf\xE9", "caf\xE9"], 'distinct:ignore_case', [0, 1]],
            'mixed string and integer' => [['1', 1], 'distinct:ignore_case', [0, 1]],
            'mixed string and boolean' => [['0', false], 'distinct:ignore_case', [1]],
            'case sensitive' => [['alpha', 'ALPHA'], 'distinct', []],
            'loose equality' => [['1', 1], 'distinct', [0, 1]],
            'strict equality' => [['1', 1], 'distinct:strict', []],
            'strict and ignore case' => [['alpha', 'ALPHA'], 'distinct:strict,ignore_case', [0, 1]],
        ];
    }

    public function test_counts_are_scoped_to_each_wildcard_group(): void
    {
        $validator = $this->validator([
            'first' => ['A', 'a'],
            'second' => ['A', 'b'],
        ], ['first.*' => 'distinct:ignore_case', 'second.*' => 'distinct:ignore_case']);

        $this->assertFalse($validator->passes());
        $this->assertSame(['first.0', 'first.1'], array_keys($validator->failed()));
    }

    public function test_top_level_numeric_attributes_report_every_duplicate(): void
    {
        $validator = $this->validator(['A', 'a'], ['*' => 'distinct:ignore_case']);

        $this->assertFalse($validator->passes());
        $this->assertSame([0, 1], array_keys($validator->failed()));
    }

    public function test_counts_are_reset_when_validation_runs_again(): void
    {
        $validator = $this->validator(['values' => ['A', 'b']], ['values.*' => 'distinct:ignore_case']);

        $this->assertTrue($validator->passes());

        $validator->setData(['values' => ['A', 'a']]);

        $this->assertFalse($validator->passes());
        $this->assertSame(['values.0', 'values.1'], array_keys($validator->failed()));

        $validator->setData(['values' => ['A', 'b']]);

        $this->assertTrue($validator->passes());
    }

    public function test_direct_comparisons_exclude_only_the_actual_attribute_value(): void
    {
        $validator = $this->validator(['values' => ['A', 'B']], ['values.*' => 'distinct:ignore_case']);

        $this->assertTrue($validator->validateDistinct('values.0', 'A', ['ignore_case']));
        $this->assertFalse($validator->validateDistinct('values.0', 'B', ['ignore_case']));
        $this->assertFalse($validator->validateDistinct('values.1', 'a', ['ignore_case']));
        $this->assertTrue($validator->validateDistinct('values.missing', 'C', ['ignore_case']));
        $this->assertTrue($validator->validateDistinct('values.missing', 'A', ['ignore_case']));

        $validator->setValue('values.0', 'B');

        $this->assertFalse($validator->validateDistinct('values.0', 'B', ['ignore_case']));
    }

    public function test_non_ascii_queries_still_use_unicode_matching(): void
    {
        $validator = $this->validator(['values' => ['K', 'B']], ['values.*' => 'distinct:ignore_case']);

        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->validateDistinct('values.1', "\u{212A}", ['ignore_case']));
    }

    public function test_nested_wildcards_report_every_duplicate(): void
    {
        $validator = $this->validator(['groups' => [
            ['values' => ['A', 'b']],
            ['values' => ['a', 'c']],
        ]], ['groups.*.values.*' => 'distinct:ignore_case']);

        $this->assertFalse($validator->passes());
        $this->assertSame(['groups.0.values.0', 'groups.1.values.0'], array_keys($validator->failed()));
    }

    public function test_counts_are_rebuilt_after_an_unsupported_group_changes(): void
    {
        $validator = $this->validator(['values' => ['A', 'é']], ['values.*' => 'distinct:ignore_case']);

        $this->assertTrue($validator->passes());

        $validator->setData(['values' => ['A', 'a']]);

        $this->assertFalse($validator->passes());
        $this->assertSame(['values.0', 'values.1'], array_keys($validator->failed()));
    }

    public function test_the_trait_still_works_without_the_count_cache_property(): void
    {
        $validator = new class
        {
            use ValidatesAttributes;

            public $data = ['values' => ['A', 'a']];

            protected function getPrimaryAttribute($attribute)
            {
                return 'values.*';
            }
        };

        $this->assertFalse($validator->validateDistinct('values.0', 'A', ['ignore_case']));
    }

    public function test_custom_comparison_scopes_are_preserved(): void
    {
        $validator = new class(new Translator(new ArrayLoader, 'en'), ['values' => ['A', 'b']], ['values.*' => 'distinct:ignore_case']) extends Validator
        {
            protected function getDistinctValues($attribute)
            {
                return $attribute === 'values.0'
                    ? ['values.0' => 'A']
                    : ['values.1' => 'b', 'other' => 'B'];
            }
        };

        $this->assertFalse($validator->passes());
        $this->assertSame(['values.1'], array_keys($validator->failed()));
    }

    private function validator(array $data, array $rules): Validator
    {
        return new Validator(new Translator(new ArrayLoader, 'en'), $data, $rules);
    }
}
