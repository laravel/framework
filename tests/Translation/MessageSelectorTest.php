<?php

namespace Illuminate\Tests\Translation;

use PHPUnit\Framework\TestCase;
use Illuminate\Translation\MessageSelector;

class MessageSelectorTest extends TestCase
{
    public function testChooseUsingPipe()
    {
        $messageSelector = new MessageSelector();
        $line = 'There is one apple|There are many apples';

        $traduction = $messageSelector->choose($line, 1, 'en_EN');
        $this->assertEquals('There is one apple', $traduction);

        $traduction = $messageSelector->choose($line, 5, 'en_EN');
        $this->assertEquals('There are many apples', $traduction);
    }

    public function testChooseUsingPipeAndRange()
    {
        $messageSelector = new MessageSelector();
        $line = '{0} There are none|[1,19] There are some|[20,*] There are many';

        $traduction = $messageSelector->choose($line, 0, 'en_EN');
        $this->assertEquals('There are none', $traduction);

        $traduction = $messageSelector->choose($line, 5, 'en_EN');
        $this->assertEquals('There are some', $traduction);

        $traduction = $messageSelector->choose($line, 50, 'en_EN');
        $this->assertEquals('There are many', $traduction);
    }

    public function getPluralIndexTestData()
    {
        /**
         * Some locales of each cases with number and expected result
         */
        return $testData = [
            'az_AZ' => [
                'number' => 0,
                'expected_result' => 0
            ],
            'bn_BD' => [
                'number' => 0,
                'expected_result' => 1
            ],
            'zu_ZA' => [
                'number' => 1,
                'expected_result' => 0
            ],
            'fi' => [
                'number' => 2,
                'expected_result' => 1
            ],
            'fil_PH' => [
                'number' => 1,
                'expected_result' => 0
            ],
            'wa_BE' => [
                'number' => 2,
                'expected_result' => 1
            ],
            'hr_HR' => [
                'number' => 21,
                'expected_result' => 0
            ],
            'uk_UA' => [
                'number' => 10,
                'expected_result' => 2
            ],
            'cs_CZ' => [
                'number' => 5,
                'expected_result' => 2
            ],
            'ga_IE' => [
                'number' => 2,
                'expected_result' => 1
            ],
            'lt_LT' => [
                'number' => 81,
                'expected_result' => 0
            ],
            'sl_SI' => [
                'number' => 5,
                'expected_result' => 3
            ],
            'mk_MK' => [
                'number' => 91,
                'expected_result' => 0
            ],
            'mt_MT' => [
                'number' => 20,
                'expected_result' => 3
            ],
            'lv_LV' => [
                'number' => 51,
                'expected_result' => 1
            ],
            'pl_PL' => [
                'number' => 25,
                'expected_result' => 2
            ],
            'cy_GB' => [
                'number' => 8,
                'expected_result' => 2
            ],
            'ro_RO' => [
                'number' => 19,
                'expected_result' => 1
            ],
            'ar_AE' => [
                'number' => 4,
                'expected_result' => 3
            ],
            'not_EXIST' => [
                'number' => 7,
                'expected_result' => 0
            ]
        ];
    }

    public function testGetPluralIndex()
    {
        $messageSelector = new MessageSelector();
        foreach ($this->getPluralIndexTestData() as $locale => $testData) {
            $plural = $messageSelector->getPluralIndex($locale, $testData['number']);
            $this->assertEquals($testData['expected_result'], $plural);
        }
    }
}
