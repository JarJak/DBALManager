<?php

namespace JarJak;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use PDO;

/**
 * universal helper class to simplify DBAL insert/update/select operations
 *
 * @package DBALManager
 * @author Jarek Jakubowski <egger1991@gmail.com>
 */
class DBALManager
{
    /**
     * @var Statement
     */
    protected $lastStatement;

    /**
     * @var Connection
     */
    protected $conn;

    /**
     * @param Connection $conn
     */
    public function setConnection(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * executes "INSERT...ON DUPLICATE KEY UPDATE" sql statement by array of parameters
     *
     * @param string $table table name
     * @param array $array values
     * @param int $updateIgnoreCount how many fields from beginning of array should be ignored on update (i.e. indexes) default: 1 (the ID)
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     *
     * @link http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
     */
    public function insertOrUpdateByArray($table, array $array, $updateIgnoreCount = 1, $excludeAutoNullColumns = false)
    {
        $cols = [];
        $params = [];
        $marks = [];
        foreach ($array as $k => $v) {
            if (false !== $excludeAutoNullColumns) {
                if (!$v && !in_array($k, $excludeAutoNullColumns)) {
                    $v = null;
                }
            }
            $cols[] = $k;
            $params[] = $v;
            $marks[] = '?';
        }
        $cols = $this->escapeSqlWords($cols);

        $sql = "INSERT INTO " . $this->escapeSqlWords($table) . " (";
        $sql .= implode(', ', $cols);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $marks);
        $sql .= ") ON DUPLICATE KEY UPDATE ";

        for ($i = 0; $i < $updateIgnoreCount; $i++) {
            array_shift($cols);
        }
        $updateArray = [];
        foreach ($cols as $col) {
            $updateArray[] = "$col=VALUES($col)";
        }
        $sql .= implode(', ', $updateArray);
        $sql .= ", id=LAST_INSERT_ID(id)";

        $this->lastStatement = $this->conn->executeQuery($sql, $params);

        return $this->conn->lastInsertId();
    }

    /**
     * returns if row has been updated or inserted
     * can only be called after insertOrUpdateByArray, throws Exception otherwise
     *
     * @return boolean|null true if updated, false if inserted, null if nothing happened
     *
     * @throws Exception
     *
     * @link http://php.net/manual/en/pdostatement.rowcount.php#109891
     */
    public function hasBeenUpdated()
    {
        if (!$this->lastStatement) {
            throw new Exception('This method should be called only after insertOrUpdateByArray.');
        }
        $rowCount = $this->lastStatement->rowCount();
        if ($rowCount === 1) {
            return false;
        } elseif ($rowCount === 2) {
            return true;
        } elseif ($rowCount === 0) {
            return null;
        } else {
            throw new Exception('This method should be called only after insertOrUpdateByArray. Got rowCount result: ' . $rowCount);
        }
    }

    /**
     * executes "INSERT" sql statement by array of parameters
     *
     * @param string $table table name
     * @param array $array values
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
     * @return int InsertId
     *
     * @deprecated you can simply use Connection::insert()
     */
    public function insertByArray($table, array $array, $excludeAutoNullColumns = false)
    {
        $cols = [];
        $params = [];
        $marks = [];
        foreach ($array as $k => $v) {
            if (false !== $excludeAutoNullColumns) {
                if (!$v && !in_array($k, $excludeAutoNullColumns)) {
                    $v = null;
                }
            }
            $cols[] = $k;
            $params[] = $v;
            $marks[] = '?';
        }
        $cols = $this->escapeSqlWords($cols);

        $sql = "INSERT INTO " . $this->escapeSqlWords($table) . " (";
        $sql .= implode(', ', $cols);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $marks);
        $sql .= ") ";

        $this->conn->executeQuery($sql, $params);

        return $this->conn->lastInsertId();
    }

    /**
     * executes "INSERT" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table table name
     * @param array $values 2-dimensional array of values to insert
     * @param array $columns columns for insert if $values are not associative
     *
     * @return int number of affected rows
     *
     * @throws Exception
     */
    public function multiInsertByArray($table, array $values, array $columns = [])
    {
        if (empty($values)) {
            return 0;
        }

        if (!$columns) {
            $columns = array_keys(current($values));
        }

        $columns = $this->escapeSqlWords($columns);

        //for integrity check
        $count = count($columns);

        $valueParts = [];
        $params = [];

        foreach ($values as $row) {
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

        $sql = "INSERT INTO " . $this->escapeSqlWords($table) . " (";
        $sql .= implode(', ', $columns);
        $sql .= ") VALUES ";
        $sql .= implode(', ', $valueParts);

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "INSERT IGNORE" sql statement by array of parameters
     *
     * @param string $table table name
     * @param array $array values
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     */
    public function insertIgnoreByArray($table, array $array, $excludeAutoNullColumns = false)
    {
        $cols = [];
        $params = [];
        $marks = [];
        foreach ($array as $k => $v) {
            if (false !== $excludeAutoNullColumns) {
                if (!$v && !in_array($k, $excludeAutoNullColumns)) {
                    $v = null;
                }
            }
            $cols[] = $k;
            $params[] = $v;
            $marks[] = '?';
        }
        $cols = $this->escapeSqlWords($cols);

        $sql = "INSERT IGNORE INTO " . $this->escapeSqlWords($table) . " (";
        $sql .= implode(', ', $cols);
        $sql .= ") VALUES (";
        $sql .= implode(', ', $marks);
        $sql .= ") ";

        $this->conn->executeQuery($sql, $params);

        return $this->conn->lastInsertId();
    }

    /**
     * executes "INSERT IGNORE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table table name
     * @param array $values 2-dimensional array of values to insert
     * @param array $columns columns for insert if $values are not associative
     *
     * @return int number of affected rows
     *
     * @throws Exception
     */
    public function multiInsertIgnoreByArray($table, array $values, array $columns = [])
    {
        if (empty($values)) {
            return 0;
        }

        if (!$columns) {
            $columns = array_keys(current($values));
        }

        $columns = $this->escapeSqlWords($columns);

        //for integrity check
        $count = count($columns);

        $valueParts = [];
        $params = [];

        foreach ($values as $row) {
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

        $sql = "INSERT IGNORE INTO " . $this->escapeSqlWords($table) . " (";
        $sql .= implode(', ', $columns);
        $sql .= ") VALUES ";
        $sql .= implode(', ', $valueParts);

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "UPDATE" sql statement by array of parameters
     *
     * @param string $table table name
     * @param array $array values
     * @param int $id
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     *
     * @deprecated you can simply use Connection::update()
     */
    public function updateByArray($table, array $array, $id, $excludeAutoNullColumns = false)
    {
        $cols = [];
        $params = [];
        $updateArray = [];

        foreach ($array as $k => $v) {
            if (false !== $excludeAutoNullColumns) {
                if (!$v && !in_array($k, $excludeAutoNullColumns)) {
                    $v = null;
                }
            }
            $cols[] = $k;
            $params[] = $v;
            $updateArray[] = $k . ' = ?';
        }
        $cols = $this->escapeSqlWords($cols);

        $sql = "UPDATE ";
        $sql .= $this->escapeSqlWords($table);
        $sql .= " SET ";
        $sql .= implode(',', $updateArray);
        $sql .= 'WHERE id = ?';

        $params[] = $id;

        $this->conn->executeQuery($sql, $params);

        return $id;
    }

    /**
     * fetch one column from all rows
     *
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return array [value1, value2, ...]
     */
    public function fetchAllColumn($sql, array $params = [], array $types = [])
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * fetch first two columns as an associative array from all rows
     *
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return array [key1 => value1, key2 => value2, ...]
     */
    public function fetchAllKeyPair($sql, array $params = [], array $types = [])
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch all rows result set as an associative array, indexed by first column
     *
     * @param string $sql
     * @param array $params
     * @param array $types
     *
     * @return array [key1 => row1, key2 => row2, ...]
     */
    public function fetchAllAssoc($sql, array $params = [], array $types = [])
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
    }

    /**
     * escape column/table names for reserved SQL words
     *
     * @param array|string $input
     *
     * @return array|string
     *
     * @throws Exception
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
     * get query with parameters in it based on QueryBuilder
     *
     * @param QueryBuilder $qb
     *
     * @return string
     */
    public static function getQuery(QueryBuilder $qb)
    {
        return self::getSqlWithParams($qb->getSQL(), $qb->getParameters());
    }

    /**
     * get query with parameters in it
     *
     * @param string $sql
     * @param array $params
     *
     * @return string
     */
    public static function getSqlWithParams($sql, array $params = [])
    {
        if (!empty($params)) {
            $indexed = $params == array_values($params);
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
     * dump query with parameters in it based on QueryBuilder
     *
     * @param QueryBuilder $query
     */
    public static function dumpQuery(QueryBuilder $query)
    {
        $string = $query->getSQL();
        $data = $query->getParameters();

        self::dumpSql($string, $data);
    }

    /**
     * dump query with parameters in it
     *
     * @param string $sql
     * @param array $params
     */
    public static function dumpSql($sql, array $params = [])
    {
        if (!function_exists('dump')) {
            trigger_error('You must install VarDumper to use SQL dumping');

            return;
        }
        dump(self::getSqlWithParams($sql, $params));
    }
}
