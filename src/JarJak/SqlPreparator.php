<?php

namespace JarJak;

use Exception;

/**
 * Separated class to ease testing and wrapping prepared SQLs into your own DB Abstraction
 *
 * @package DBALManager
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlPreparator
{
    /**
     * prepares "INSERT...ON DUPLICATE KEY UPDATE" sql statement by array of parameters
     *
     * @param string $table           table name
     * @param array  $values          values
     * @param array  $ignoreForUpdate columns that should not be updated
     *
     * @return array [sql, params] prepared SQL and params
     *
     * @link http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
     */
    public static function prepareInsertOrUpdate($table, array $values, array $ignoreForUpdate = [])
    {
        $cols = [];
        $params = [];
        $marks = [];

        foreach ($values as $k => $v) {
            $cols[] = $k;
            $params[] = $v;
            $marks[] = '?';
        }

        $cols = static::escapeSqlWords($cols);
        $ignoreForUpdate = static::escapeSqlWords($ignoreForUpdate);

        $sql = "INSERT INTO " . static::escapeSqlWords($table) . " (";
        $sql .= implode(', ', $cols);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $marks);
        $sql .= ") ON DUPLICATE KEY UPDATE ";

        $updateArray = [];
        foreach ($cols as $col) {
            if (!in_array($col, $ignoreForUpdate)) {
                $updateArray[] = "$col=VALUES($col)";
            }
        }
        $sql .= implode(', ', $updateArray);
        $sql .= ", id=LAST_INSERT_ID(id)";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * prepares "INSERT...ON DUPLICATE KEY UPDATE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table           table name
     * @param array  $rows            2-dimensional array of values to insert
     * @param array  $ignoreForUpdate columns that should not be updated
     *
     * @return array [sql, params] prepared SQL and params
     *
     * @throws Exception when number of columns and values does not match
     */
    public static function prepareMultiInsertOrUpdate($table, array $rows, array $ignoreForUpdate = [])
    {
        $columns = static::extractColumnsFromRows($rows);

        /**
         * since PHP 7.1 this can look like:
         * ['params' => $params, 'valueParts' => $valueParts] = static::generateMultiParams($rows, $columns)
         */
        list($params, $valueParts) = array_values(static::generateMultiParams($rows, $columns));

        $sql = 'INSERT INTO ' . $table;
        $sql .= ' (' . implode(', ', $columns) . ')';
        $sql .= " VALUES ";
        $sql .= implode(', ', $valueParts);
        $sql .= " ON DUPLICATE KEY UPDATE ";

        $updateArray = [];
        foreach ($columns as $col) {
            if (!in_array($col, $ignoreForUpdate)) {
                $updateArray[] = "$col=VALUES($col)";
            }
        }
        $sql .= implode(', ', $updateArray);

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * prepares "INSERT" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table   table name
     * @param array  $rows    2-dimensional array of values to insert
     * @param array  $columns columns for insert if $values are not associative
     *
     * @return array [sql, params] prepared SQL and params
     *
     * @throws Exception when number of columns and values does not match
     */
    public static function prepareMultiInsert($table, array $rows, array $columns = [])
    {
        if (!$columns) {
            $columns = static::extractColumnsFromRows($rows);
        }

        $columns = static::escapeSqlWords($columns);

        list($params, $valueParts) = array_values(static::generateMultiParams($rows, $columns));

        $sql = "INSERT INTO " . static::escapeSqlWords($table) . " (";
        $sql .= implode(', ', $columns);
        $sql .= ") VALUES ";
        $sql .= implode(', ', $valueParts);

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * prepares "INSERT IGNORE" sql statement by array of parameters
     *
     * @param string $table  table name
     * @param array  $values values for columns
     *
     * @return array [sql, params] prepared SQL and params
     */
    public static function prepareInsertIgnore($table, array $values)
    {
        $cols = [];
        $params = [];
        $marks = [];

        foreach ($values as $k => $v) {
            $cols[] = $k;
            $params[] = $v;
            $marks[] = '?';
        }

        $cols = static::escapeSqlWords($cols);

        $sql = "INSERT IGNORE INTO " . static::escapeSqlWords($table) . " (";
        $sql .= implode(', ', $cols);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $marks);
        $sql .= ") ";

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * prepares "INSERT IGNORE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table   table name
     * @param array  $rows    2-dimensional array of values to insert
     * @param array  $columns columns for insert if $values are not associative
     *
     * @return array [sql, params] prepared SQL and params
     *
     * @throws Exception when number of columns and values does not match
     */
    public static function prepareMultiInsertIgnore($table, array $rows, array $columns = [])
    {
        if (!$columns) {
            $columns = static::extractColumnsFromRows($rows);
        }

        $columns = static::escapeSqlWords($columns);

        list($params, $valueParts) = array_values(static::generateMultiParams($rows, $columns));

        $sql = "INSERT IGNORE INTO " . static::escapeSqlWords($table) . " (";
        $sql .= implode(', ', $columns);
        $sql .= ") VALUES ";
        $sql .= implode(', ', $valueParts);

        return ['sql' => $sql, 'params' => $params];
    }

    /**
     * @param array $params        sql params to parse
     * @param array $ignoreColumns columns that should be ignored in this function, like those where zero is accepted
     *                             value
     *
     * @return array
     */
    public static function setNullValues(array $params, array $ignoreColumns = [])
    {
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = static::setNullValues($v, $ignoreColumns);
            }
            if (!$v && !in_array($k, $ignoreColumns)) {
                $params[$k] = null;
            }
        }

        return $params;
    }

    /**
     * escape column/table names for reserved SQL words
     *
     * @param array|string $input
     *
     * @return array|string
     *
     * @throws Exception when input is empty
     */
    public static function escapeSqlWords($input)
    {
        if (!$input) {
            throw new Exception('Empty input');
        }

        $escapeFunction = function ($value) {
            return '`' . preg_replace('/[^A-Za-z0-9_]+/', '', $value) . '`';
        };

        if (is_array($input)) {
            return array_map($escapeFunction, $input);
        } else {
            return $escapeFunction($input);
        }
    }

    /**
     * @param array $rows
     *
     * @return array columns
     */
    public static function extractColumnsFromRows(array $rows)
    {
        return array_keys(current($rows));
    }

    /**
     * @param array $rows
     * @param array $columns
     *
     * @return array [params, valueParts]
     *
     * @throws Exception when number of columns and values does not match
     */
    protected static function generateMultiParams(array $rows, array $columns)
    {
        //for integrity check
        $count = count($columns);

        $valueParts = [];
        $params = [];

        foreach ($rows as $row) {
            if (count($row) !== $count) {
                throw new Exception("Number of columns and values does not match.");
            }
            $marks = [];
            foreach ($row as $value) {
                $marks[] = '?';
                $params[] = $value;
            }
            $valueParts[] = '(' . implode(',', $marks) . ')';
        }

        return ['params' => $params, 'valueParts' => $valueParts];
    }
}
