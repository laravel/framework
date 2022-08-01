<?php

namespace Illuminate\Foundation\Console;

use Symfony\Component\VarDumper\Cloner\Cursor;
use Symfony\Component\VarDumper\Dumper\CliDumper as BaseCliDumper;

class CliDumper extends BaseCliDumper
{
    /**
     * The style colors list.
     *
     * @var array<string, string>
     */
    protected $styles = [
        // Base Styles...
        'default' => '0;38;5;247',
        'num' => '1;38;5;202',
        'const' => '1;38;5;202',
        'str' => '1;38;5;10',
        'note' => '38;5;81',
        'ref' => '38;5;247',
        'public' => '38;5;221',
        'protected' => '38;5;221',
        'private' => '38;5;221',
        'meta' => '38;5;170',
        'key' => '38;5;176',
        'index' => '38;5;221',

        // Custom Styles...
        'boolean-true' => '1;38;5;186',
        'boolean-false' => '1;38;5;203',
    ];

    /**
     * {@inheritdoc}
     */
    public function dumpScalar(Cursor $cursor, string $type, string|int|float|bool|null $value)
    {
        if (! in_array($type, ['boolean'])) {
            return parent::dumpScalar($cursor, $type, $value);
        }

        $this->dumpKey($cursor);

        switch ($type) {
            case 'boolean':
                $style = sprintf('boolean-%s',  $value = $value ? 'true' : 'false');
                break;
        }

        $this->line .= $this->style($style, $value, $cursor->attr);

        $this->endValue($cursor);
    }
}
