<?php

declare(strict_types=1);

namespace JarJak;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use JarJak\Exception\DBALManagerException;
use PDO;

/**
 * Universal helper class to simplify DBAL insert/update/select operations
 *
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class DBALManager
{
    /**
     * @var Statement|null
     */
    protected $lastStatement;

    /**
     * @var Connection
     */
    protected $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * executes "INSERT...ON DUPLICATE KEY UPDATE" sql statement by array of parameters
     *
     * @param string     $table               table name
     * @param array      $values              values
     * @param int        $updateIgnoreCount   how many fields from beginning of array should be ignored on update
     *                                        (i.e. indexes) default: 1 (the ID)
     * @param array|null $excludeEmptyColumns array of columns that can contain zero-equal values, set to null
     *                                        if you want to disable auto-null entirely [default: null]
     *
     * @return string|int InsertId
     *
     * @see http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
     */
    public function insertOrUpdate(
        string $table,
        array $values,
        int $updateIgnoreCount = 1,
        ?array $excludeEmptyColumns = null
    ) {
        if (null !== $excludeEmptyColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeEmptyColumns);
        }

        if ($updateIgnoreCount) {
            $ignoreForUpdate = array_slice(array_keys($values), 0, $updateIgnoreCount);
        } else {
            $ignoreForUpdate = [];
        }

        [$sql, $params] = array_values(SqlPreparator::prepareInsertOrUpdate($table, $values, $ignoreForUpdate));

        $this->lastStatement = $this->conn->executeUpdate($sql, $params);

        return $this->getLastInsertId();
    }

    /**
     * returns if row has been updated or inserted
     * can only be called after insertOrUpdate, throws DBALManagerException otherwise
     *
     * @return bool|null true if updated, false if inserted, null if nothing happened
     *
     * @throws DBALManagerException
     *
     * @see http://php.net/manual/en/pdostatement.rowcount.php#109891
     */
    public function hasBeenUpdated(): ?bool
    {
        if (! $this->lastStatement) {
            throw new DBALManagerException('This method should be called only after insertOrUpdate.');
        }

        $rowCount = $this->lastStatement->rowCount();
        if ($rowCount === 1) {
            return false;
        } elseif ($rowCount === 2) {
            return true;
        } elseif ($rowCount === 0) {
            return null;
        }

        throw new DBALManagerException('This method should be called only after insertOrUpdate. Got rowCount result: ' . $rowCount);
    }

    /**
     * executes "INSERT...ON DUPLICATE KEY UPDATE" sql statement by multi array of values
     * to be used for bulk inserts
     *
     * @param string     $table               table name
     * @param array      $rows                2-dimensional array of values to insert
     * @param int        $updateIgnoreCount   how many fields from beginning of array should be ignored on update
     *                                        (i.e. indexes) default: 1 (the ID)
     * @param array|null $excludeEmptyColumns array of columns that can contain zero-equal values,
     *                                        set to null if you want to disable auto-null entirely [default: null]
     * @param bool       $returnAsArray       instead of returning affected rows, returns ['inserted' => int, 'updated' => int]
     *
     * @return int|array number of affected rows or ['inserted' => int, 'updated' => int] if $returnAsArray = true
     *
     * @throws DBALManagerException
     */
    public function multiInsertOrUpdate(
        string $table,
        array $rows,
        int $updateIgnoreCount = 1,
        ?array $excludeEmptyColumns = null,
        bool $returnAsArray = false
    ) {
        if (empty($rows)) {
            return 0;
        }

        if (null !== $excludeEmptyColumns) {
            $rows = SqlPreparator::setNullValues($rows, $excludeEmptyColumns);
        }

        if ($updateIgnoreCount) {
            $ignoreForUpdate = array_slice(SqlPreparator::extractColumnsFromRows($rows), 0, $updateIgnoreCount);
        } else {
            $ignoreForUpdate = [];
        }

        [$sql, $params] = array_values(SqlPreparator::prepareMultiInsertOrUpdate($table, $rows, $ignoreForUpdate));

        $affected = $this->conn->executeUpdate($sql, $params);

        if ($returnAsArray) {
            $rowCount = count($rows);
            $result = [
                'inserted' => 0,
                'updated' => 0,
            ];
            $result['updated'] = $rowCount - $affected;
            $result['inserted'] = $rowCount - $result['updated'];
            return $result;
        }

        return $affected;
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
     * @throws DBALManagerException
     */
    public function multiInsert(string $table, array $rows, array $columns = []): int
    {
        if (empty($rows)) {
            return 0;
        }

        [$sql, $params] = array_values(SqlPreparator::prepareMultiInsert($table, $rows, $columns));

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * executes "INSERT IGNORE" sql statement by array of parameters
     *
     * @param string     $table               table name
     * @param array      $values              values
     * @param array|null $excludeEmptyColumns array of columns that can contain zero-equal values, set to null
     *                                        if you want to disable auto-null entirely [default: null]
     *
     * @return int|string InsertId
     */
    public function insertIgnore(string $table, array $values, ?array $excludeEmptyColumns = null)
    {
        if (null !== $excludeEmptyColumns) {
            $values = SqlPreparator::setNullValues($values, $excludeEmptyColumns);
        }

        [$sql, $params] = array_values(SqlPreparator::prepareInsertIgnore($table, $values));

        $this->conn->executeUpdate($sql, $params);

        return $this->getLastInsertId();
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
     * @throws DBALManagerException
     */
    public function multiInsertIgnore(string $table, array $rows, array $columns = []): int
    {
        if (empty($rows)) {
            return 0;
        }

        ['sql' => $sql, 'params' => $params] = SqlPreparator::prepareMultiInsertIgnore($table, $rows, $columns);

        return $this->conn->executeUpdate($sql, $params);
    }

    /**
     * @return int|string
     */
    protected function getLastInsertId()
    {
        $lastInsertId = $this->conn->lastInsertId();
        return is_numeric($lastInsertId) ? (int) $lastInsertId : $lastInsertId;
    }

    /**
     * fetch one column from all rows
     *
     * @return array [value1, value2, ...]
     */
    public function fetchAllColumn(string $sql, array $params = [], array $types = []): array
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * fetch first two columns as an associative array from all rows
     *
     * @return array [key1 => value1, key2 => value2, ...]
     */
    public function fetchAllKeyPair(string $sql, array $params = [], array $types = []): array
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * fetch all rows result set as an associative array, indexed by first column
     *
     * @return array [key1 => row1, key2 => row2, ...]
     */
    public function fetchAllAssoc(string $sql, array $params = [], array $types = []): array
    {
        $stmt = $this->conn->executeQuery($sql, $params, $types);

        return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
    }
}
