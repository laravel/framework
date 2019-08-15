<?php

namespace Illuminate\Tests\Translation;

use PHPUnit\Framework\TestCase;
use Illuminate\Translation\DecimalQuantity;

class DecimalQuantityTest extends TestCase
{
    /**
     * @dataProvider pluralOperandData
     */
    public function testPluralOperand($expected, $operand, $number)
    {
        $dc = new DecimalQuantity($number);

        $this->assertEquals($expected, $dc->getPluralOperand($operand));
    }

    public function pluralOperandData()
    {
        return [
            [1, 'n', 1],
            [1, 'i', 1],
            [0, 'v', 1],
            [0, 'w', 1],
            [0, 'f', 1],
            [0, 't', 1],

            [1, 'n', '1.0'],
            [1, 'i', '1.0'],
            [1, 'v', '1.0'],
            [0, 'w', '1.0'],
            [0, 'f', '1.0'],
            [0, 't', '1.0'],

            [1, 'n', '-1.00'],
            [1, 'i', '-1.00'],
            [2, 'v', '-1.00'],
            [0, 'w', '-1.00'],
            [0, 'f', '-1.00'],
            [0, 't', '-1.00'],

            [2.3, 'n', 2.3],
            [2, 'i', 2.3],
            [1, 'v', 2.3],
            [1, 'w', 2.3],
            [3, 'f', 2.3],
            [3, 't', 2.3],

            [9.3, 'n', '9.30'],
            [9, 'i', '9.30'],
            [2, 'v', '9.30'],
            [1, 'w', '9.30'],
            [30, 'f', '9.30'],
            [3, 't', '9.30'],

            [7.03, 'n', '-7.03'],
            [7, 'i', '-7.03'],
            [2, 'v', '-7.03'],
            [2, 'w', '-7.03'],
            [3, 'f', '-7.03'],
            [3, 't', '-7.03'],

            [3.23, 'n', '-3.230'],
            [3, 'i', '-3.230'],
            [3, 'v', '-3.230'],
            [2, 'w', '-3.230'],
            [230, 'f', '-3.230'],
            [23, 't', '-3.230'],
        ];
    }
}
