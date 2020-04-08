<?php

namespace Illuminate\Database\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager as DoctrineMySqlSchemaManager;
use Doctrine\DBAL\Types\Type;
use Illuminate\Support\Str;

class MySqlSchemaManager extends DoctrineMySqlSchemaManager
{
    /**
     * Gets Table Column Definition for Enum Column Type.
     *
     * @param array $tableColumn
     * @return \Doctrine\DBAL\Schema\Column
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function getPortableTableEnumColumnDefinition(array $tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $type = $this->_platform->getDoctrineTypeMapping('enum');

        // In cases where not connected to a database DESCRIBE $table does not return 'Comment'
        if (isset($tableColumn['comment'])) {
            $type = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }

        $options = [
            'length' => null,
            'unsigned' => false,
            'fixed' => null,
            'default' => $tableColumn['default'] ?? null,
            'notnull' => $tableColumn['null'] !== 'YES',
            'scale' => null,
            'precision' => null,
            'autoincrement' => false,
            'comment' => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        ];

        $column = new Column($tableColumn['field'], Type::getType($type), $options);
        $column->setCustomSchemaOption('allowedOptions', $this->getAllowedEnumOptions($tableColumn));

        if (isset($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }

    /**
     * {@inheritdoc}
     */
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $keys = array_change_key_case($tableColumn, CASE_LOWER);

        $type = strtok(strtolower($keys['type']), '(), ');

        if ($type === 'enum') {
            return $this->getPortableTableEnumColumnDefinition($tableColumn);
        }

        return parent::_getPortableTableColumnDefinition($tableColumn);
    }

    /**
     * Get enum options from the column.
     *
     * @param $tableColumn
     * @return array
     */
    protected function getAllowedEnumOptions($tableColumn)
    {
        return Str::of($tableColumn['type'])->replaceMatches('/enum\((?P<allowedOptions>(.*))\)/', function ($matched) {
            return Str::of($matched['allowedOptions'])->replace("'", '');
        })->explode(',')->toArray();
    }
}
