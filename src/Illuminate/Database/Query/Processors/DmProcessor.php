<?php

namespace Illuminate\Database\Query\Processors;

use Exception;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class DmProcessor extends Processor
{

    /**
     * Process an "insert get ID" query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  string  $sql
     * @param  array  $values
     * @param  string|null  $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        $connection = $query->getConnection();

        $connection->insert($sql, $values);

        $id = $connection->getPdo()->lastInsertId();

        return is_numeric($id) ? (int) $id : $id;
    }

    /**
     * Process the results of a tables query.
     *
     * @param  array  $results
     * @return array
     */
    public function processTables($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->NAME,
                'schema' => $result->SCHNAME,
                'size' => $result->TABLE_USED,
                'comment' => $result->COMMENTS ?? null,
            ];
        }, $results);
    }

    /**
     * Process the results of a views query.
     *
     * @param  array  $results
     * @return array
     */
    public function processViews($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'NAME' => $result->NAME,
                'SCHNAME' => $result->SCHNAME ?? null, 
                'CRTDATE' => $result->CRTDATE,
            ];
        }, $results);
    }
    
    /**
     * Process the results of an indexes query.
     *
     * @param  array  $results
     * @return array
     */
    public function processIndexes($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => strtolower($result->NAME),
                'columns' => $result->COLUMNS,
                'type' => strtolower($result->TYPE),
                'unique' => $result->ISUNIQUE == 'Y' ? true : false,
            ];
        }, $results);
    }

    /**
     * Process the results of a columns query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumns($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->NAME,
                'type' => $result->TYPE_NAME,
                'nullable' => $result->NULLABLE == 'Y' ? true : false,
                'default' => $result->DEFVAL,
                'auto_increment' => $result->AUTO_INCREMENT,
                'comment' => $result->COL_COMMENT,
                'length' => $result->LENGTH,
                'virtual' => ($result->VIR_COL & 0x01) == 1 ? true : false
                ];
            }, $results);
    }

    /**
     * Process the results of a class name query.
     *
     * @param  array  $results
     * @return array
     */
    public function processClassName($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'class_name' => $result->SCH.'.'.$result->PKG,
            ];
        }, $results);
    }

    /**
     * Process the results of a foreign keys query.
     *
     * @param  array  $results
     * @return array
     */
    public function processForeignKeys($results)
    {
        return array_map(function ($result) {
            $result = (object) $result;

            return [
                'name' => $result->CONSTRAINT_NAME,
                'columns' => explode(',', $result->COL_NAME),
                'foreign_schema' => $result->FOREIGN_SCHEMA,
                'foreign_table' => $result->FOREIGN_TABLE,
                'foreign_columns' => explode(',', $result->FOREIGN_COLUMNS),
            ];
        }, $results);
    }

    /**
     * Process the results of a columns query,set the 'AUTO_INCREMENT' and change the class name.
     *
     * @param  array  $results
     * @param  array  $idenLists
     * @param  array  $classNameLists
     * @return array
     */
    public function processColumnsIncrementClassName($results, $idenLists, $classNameLists)
    {
        return array_map(function ($result, $idenList, $classNameList) {
            $result = (object) $result;
            $idenList = (object) $idenList;
            $classNameList = (object) $classNameList;
            $autoIncrement = false;

            if (isset($idenList->NAME)) {
                if ($result->NAME == $idenList->NAME) {
                    $autoIncrement = true;
                }
            }

            return [
                'NAME' => $result->NAME,
                'TYPE_NAME' => $classNameList->scalar,
                'NULLABLE' => $result->NULLABLE,
                'DEFVAL' => $result->DEFVAL,
                'AUTO_INCREMENT' => $autoIncrement,
                'COL_COMMENT' => $result->COL_COMMENT,
                'LENGTH' => $result->LENGTH,
                'VIR_COL' => $result->VIR_COL,
            ];
        }, $results, $idenLists, $classNameLists);
    }
}
