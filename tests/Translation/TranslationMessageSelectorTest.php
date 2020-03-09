<?php

namespace Illuminate\Tests\Translation;

use Illuminate\Translation\MessageSelector;
use PHPUnit\Framework\TestCase;

class TranslationMessageSelectorTest extends TestCase
{
    /**
     * @dataProvider chooseTestData
     */
    public function testChoose($expected, $id, $number)
    {
        $selector = new MessageSelector;

        $this->assertEquals($expected, $selector->choose($id, $number, 'en'));
    }

    /**
     * @return \Generator
     */
    public function chooseTestData()
    {
            yield ['first', 'first', 1];
            yield ['first', 'first', 10];
            yield ['first', 'first|second', 1];
            yield ['second', 'first|second', 10];
            yield ['second', 'first|second', 0];

            yield ['first', '{0}  first|{1}second', 0];
            yield ['first', '{1}first|{2}second', 1];
            yield ['second', '{1}first|{2}second', 2];
            yield ['first', '{2}first|{1}second', 2];
            yield ['second', '{9}first|{10}second', 0];
            yield ['first', '{9}first|{10}second', 1];
            yield ['', '{0}|{1}second', 0];
            yield ['', '{0}first|{1}', 1];
            yield ['first', '{1.3}first|{2.3}second', 1.3];
            yield ['second', '{1.3}first|{2.3}second', 2.3];
            yield ['first
            line', '{1}first
            line|{2}second', 1];
            ["first \n
            line", "{1}first \n
            line|{2}second", 1];

            yield ['first', '{0}  first|[1,9]second', 0];
            yield ['second', '{0}first|[1,9]second', 1];
            yield ['second', '{0}first|[1,9]second', 10];
            yield ['first', '{0}first|[2,9]second', 1];
            yield ['second', '[4,*]first|[1,3]second', 1];
            yield ['first', '[4,*]first|[1,3]second', 100];
            yield ['second', '[1,5]first|[6,10]second', 7];
            yield ['first', '[*,4]first|[5,*]second', 1];
            yield ['second', '[5,*]first|[*,4]second', 1];
            yield ['second', '[5,*]first|[*,4]second', 0];

            yield ['first', '{0}first|[1,3]second|[4,*]third', 0];
            yield ['second', '{0}first|[1,3]second|[4,*]third', 1];
            yield ['third', '{0}first|[1,3]second|[4,*]third', 9];

            yield ['first', 'first|second|third', 1];
            yield ['second', 'first|second|third', 9];
            yield ['second', 'first|second|third', 0];

            yield ['first', '{0}  first | { 1 } second', 0];
            yield ['first', '[4,*]first | [1,3]second', 100];
    }
}
