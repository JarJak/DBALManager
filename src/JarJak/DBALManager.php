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
 * @author  Jarek Jakubowski <egger1991@gmail.com>
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
     * @param string        $table                  table name
     * @param array         $values                 values
     * @param int           $updateIgnoreCount      how many fields from beginning of array should be ignored on update
     *                                              (i.e. indexes) default: 1 (the ID)
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false
     *                                              if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     *
     * @link http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
     */
    public function insertOrUpdateByArray($table, array $values, $updateIgnoreCount = 1, $excludeAutoNullColumns = false)
    {
        if (false !== $excludeAutoNullColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeAutoNullColumns);
        }

        if ($updateIgnoreCount) {
            $ignoreForUpdate = array_slice(array_keys($values), 0, $updateIgnoreCount);
        } else {
            $ignoreForUpdate = [];
        }

        list($sql, $params) = array_values(SqlPreparator::prepareInsertOrUpdate($table, $values, $ignoreForUpdate));

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
     * executes "INSERT...ON DUPLICATE KEY UPDATE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string        $table                  table name
     * @param array         $rows                   2-dimensional array of values to insert
     * @param int           $updateIgnoreCount      how many fields from beginning of array should be ignored on update
     *                                              (i.e. indexes) default: 1 (the ID)
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false
     *                                              if you want to disable auto-null entirely [default: false]
     *
     * @return int number of affected rows
     *
     * @throws Exception
     */
    public function multiInsertOrUpdateByArray($table, array $rows, $updateIgnoreCount = 1, $excludeAutoNullColumns = false)
    {
        if (empty($rows)) {
            return 0;
        }

        if (false !== $excludeAutoNullColumns) {
            $rows = SqlPreparator::setNullValues($rows, $excludeAutoNullColumns);
        }

        if ($updateIgnoreCount) {
            $ignoreForUpdate = array_slice(SqlPreparator::extractColumnsFromRows($rows), 0, $updateIgnoreCount);
        } else {
            $ignoreForUpdate = [];
        }

        list($sql, $params) = array_values(SqlPreparator::prepareMultiInsertOrUpdate($table, $rows, $ignoreForUpdate));

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "INSERT" sql statement by array of parameters
     *
     * @param string        $table                  table name
     * @param array         $values                 values
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false
     *                                              if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     *
     * @deprecated you can simply use Connection->insert()
     */
    public function insertByArray($table, array $values, $excludeAutoNullColumns = false)
    {
        if (false !== $excludeAutoNullColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeAutoNullColumns);
        }

        $this->conn->insert($table, $values);

        return $this->conn->lastInsertId();
    }

    /**
     * executes "INSERT" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table   table name
     * @param array  $rows    2-dimensional array of values to insert
     * @param array  $columns columns for insert if $values are not associative
     *
     * @return int number of affected rows
     *
     * @throws Exception
     */
    public function multiInsertByArray($table, array $rows, array $columns = [])
    {
        if (empty($rows)) {
            return 0;
        }

        list($sql, $params) = array_values(SqlPreparator::prepareMultiInsert($table, $rows, $columns));

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "INSERT IGNORE" sql statement by array of parameters
     *
     * @param string        $table                  table name
     * @param array         $values                 values
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false
     *                                              if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     */
    public function insertIgnoreByArray($table, array $values, $excludeAutoNullColumns = false)
    {
        if (false !== $excludeAutoNullColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeAutoNullColumns);
        }

        list($sql, $params) = array_values(SqlPreparator::prepareInsertIgnore($table, $values));

        $this->conn->executeQuery($sql, $params);

        return $this->conn->lastInsertId();
    }

    /**
     * executes "INSERT IGNORE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string $table   table name
     * @param array  $rows    2-dimensional array of values to insert
     * @param array  $columns columns for insert if $values are not associative
     *
     * @return int number of affected rows
     *
     * @throws Exception
     */
    public function multiInsertIgnoreByArray($table, array $rows, array $columns = [])
    {
        if (empty($rows)) {
            return 0;
        }

        list($sql, $params) = array_values(SqlPreparator::prepareMultiInsertIgnore($table, $rows, $columns));

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "UPDATE" sql statement by array of parameters
     *
     * @param string        $table                  table name
     * @param array         $values                 values
     * @param int           $id
     * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false
     *                                              if you want to disable auto-null entirely [default: false]
     *
     * @return int InsertId
     *
     * @deprecated you can simply use Connection->update()
     */
    public function updateByArray($table, array $values, $id, $excludeAutoNullColumns = false)
    {
        if (false !== $excludeAutoNullColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeAutoNullColumns);
        }

        $this->conn->update($table, $values, ['id' => $id]);

        return $id;
    }

    /**
     * fetch one column from all rows
     *
     * @param string $sql
     * @param array  $params
     * @param array  $types
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
     * @param array  $params
     * @param array  $types
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
     * @param array  $params
     * @param array  $types
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
     *
     * @deprecated use SqlPreparator::escapeSqlWords()
     */
    public static function escapeSqlWords($input)
    {
        return SqlPreparator::escapeSqlWords($input);
    }

    /**
     * dump query with parameters in it based on QueryBuilder
     *
     * @param QueryBuilder $query
     *
     * @deprecated use SqlDumper::dumpQuery()
     */
    public static function dumpQuery(QueryBuilder $query)
    {
        SqlDumper::dumpQuery($query);
    }

    /**
     * dump query with parameters in it
     *
     * @param string $sql
     * @param array  $params
     *
     * @deprecated use SqlDumper::dumpSql()
     */
    public static function dumpSql($sql, array $params = [])
    {
        SqlDumper::dumpSql($sql, $params);
    }
}
