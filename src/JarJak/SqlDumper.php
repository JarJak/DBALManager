<?php

namespace JarJak;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Use these to get SQL ready to capy-paste, with parameters already in it
 *
 * @package DBALManager
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlDumper
{
    /**
     * get query with parameters in it
     *
     * @param string $sql
     * @param array  $params
     *
     * @return string
     */
    public static function getSqlWithParams($sql, array $params = [])
    {
        if (!empty($params)) {
            $indexed = ($params == array_values($params));
            foreach ($params as $k => $v) {
                if (is_string($v)) {
                    $v = "'$v'";
                }
                if (is_array($v)) {
                    $v = "'" . implode("','", $v) . "'";
                }
                if ($indexed) {
                    $sql = preg_replace('/\?/', $v, $sql, 1);
                } else {
                    $sql = str_replace(":$k", $v, $sql);
                }
            }
        }

        $sql = str_replace(PHP_EOL, '', $sql);

        return $sql;
    }

    /**
     * get query with parameters in it based on QueryBuilder
     *
     * @param QueryBuilder $qb
     *
     * @return string
     */
    public static function getQuery(QueryBuilder $qb)
    {
        return static::getSqlWithParams($qb->getSQL(), $qb->getParameters());
    }

    /**
     * dump query with parameters in it based on QueryBuilder
     *
     * @param QueryBuilder $query
     */
    public static function dumpQuery(QueryBuilder $query)
    {
        static::dump(static::getQuery($query));
    }

    /**
     * dump query with parameters in it
     *
     * @param string $sql
     * @param array  $params
     */
    public static function dumpSql($sql, array $params = [])
    {
        static::dump(static::getSqlWithParams($sql, $params));
    }

    /**
     * wrapper for dump() function from VarDumper
     * fallbacks to var_dump
     *
     * @param $var
     */
    public static function dump($var)
    {
        if (function_exists('dump')) {
            dump($var);
        } else {
            var_dump($var);
        }
    }
}
