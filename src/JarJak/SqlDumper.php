<?php

declare(strict_types=1);

namespace JarJak;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Use these to get SQL ready to capy-paste, with parameters already in it
 *
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlDumper
{
    private function __construct()
    {
    }

    /**
     * get query with parameters in it
     */
    public static function getSqlWithParams(string $sql, array $params = []): string
    {
        if (! empty($params)) {
            $indexed = ($params === array_values($params));
            foreach ($params as $k => $v) {
                if (is_string($v)) {
                    $v = "'${v}'";
                }
                if (is_array($v)) {
                    $v = "'" . implode("','", $v) . "'";
                }
                if ($indexed) {
                    $sql = preg_replace('/\?/', $v, $sql, 1);
                } else {
                    $sql = str_replace(":${k}", $v, $sql);
                }
            }
        }

        return str_replace(PHP_EOL, '', $sql);
    }

    /**
     * get query with parameters in it based on QueryBuilder
     */
    public static function getQuery(QueryBuilder $qb): string
    {
        return static::getSqlWithParams($qb->getSQL(), $qb->getParameters());
    }

    /**
     * dump query with parameters in it based on QueryBuilder
     */
    public static function dumpQuery(QueryBuilder $query): void
    {
        static::dump(static::getQuery($query));
    }

    /**
     * dump query with parameters in it
     */
    public static function dumpSql(string $sql, array $params = []): void
    {
        static::dump(static::getSqlWithParams($sql, $params));
    }

    /**
     * wrapper for dump() function from VarDumper
     * fallbacks to var_dump
     */
    public static function dump($var): void
    {
        if (function_exists('dump')) {
            dump($var);
        } else {
            var_dump($var);
        }
    }
}
