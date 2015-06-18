<?php

namespace JarJak;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Exception;
use PDO;

/**
 * universal helper class to simplify DBAL insert/update/select operations
 * @package DBALManager
 * @author Jarek Jakubowski <egger1991@gmail.com>
 */
class DBALManager
{
	
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
	 * executes "INSERT...ON DUPLICATE KEY UPDATE" sql statement by array of parameters
	 * @param string $table table name
	 * @param array $array values
	 * @param int $updateIgnoreCount how many fields from beginning of array should be ignored on update (i.e. indexes) default: 1 (the ID)
	 * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
	 * @return int InsertId
	 * @link http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
	 */
	public function insertOrUpdateByArray($table, array $array, $updateIgnoreCount = 1, $excludeAutoNullColumns = false)
	{		
		$cols = array();
		$params = array();
		$marks = array();
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
		$updateArray = array();
		foreach ($cols as $col) {
			$updateArray[] = "$col=VALUES($col)";
		}
		$sql .= implode(', ', $updateArray);
		$sql .= ", id=LAST_INSERT_ID(id)";

		$this->conn->executeQuery($sql, $params);
		return $this->conn->lastInsertId();
	}

	/**
	 * executes "INSERT" sql statement by array of parameters
	 * @param string $table table name
	 * @param array $array values
	 * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
	 * @return int InsertId
	 */
	public function insertByArray($table, array $array, $excludeAutoNullColumns = false)
	{
		$cols = array();
		$params = array();
		$marks = array();
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
	 * @param string $table table name
	 * @param array $values 2-dimensional array of values to insert
	 * @param array $columns columns for insert if $values is not associative
	 * @return int number of affected rows
	 */
	public function multiInsertByArray($table, array $values, array $columns = array())
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
			if(count($row) !== $count) {
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
	 * @param string $table table name
	 * @param array $array values
	 * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
	 * @return int InsertId
	 */
	public function insertIgnoreByArray($table, array $array, $excludeAutoNullColumns = false)
	{
		$cols = array();
		$params = array();
		$marks = array();
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
	 * executes "UPDATE" sql statement by array of parameters
	 * @param string $table table name
	 * @param array $array values
	 * @param int $id
	 * @param array|boolean $excludeAutoNullColumns array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely [default: false]
	 * @return int InsertId
	 */
	public function updateByArray($table, array $array, $id, $excludeAutoNullColumns = false)
	{
		$cols = array();
		$params = array();
		$marks = array();

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

		$sql = "UPDATE " . $this->escapeSqlWords($table) . " SET ";

		$updateArray = array();
		foreach ($cols as $col) {
			$updateArray[] = $col . ' = ?';
		}

		$sql .= implode(',', $updateArray);

		$sql .= 'WHERE id = ?';

		$params[] = $id;

		$this->conn->executeQuery($sql, $params);
		return $id;
	}
	
	/**
	 * fetch one column from all rows
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 * @return array [value1, value2, ...]
	 */
	public function fetchAllColumn($sql, array $params = [], array $types = [])
	{
		$stmt = $this->conn->executeQuery($sql, $params, $types);
		return $stmt->fetchAll(PDO::FETCH_COLUMN);
	}
	
	/**
	 * fetch first two columns as an associative array from all rows
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 * @return array [key1 => value1, key2 => value2, ...]
	 */
	public function fetchAllKeyPair($sql, array $params = [], array $types = [])
	{
		$stmt = $this->conn->executeQuery($sql, $params, $types);
		return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
	}
	
	/**
	 * fetch all rows result set as an associative array, indexed by first column
	 * @param string $sql
	 * @param array $params
	 * @param array $types
	 * @return array [key1 => row1, key2 => row2, ...]
	 */
	public function fetchAllAssoc($sql, array $params = [], array $types = [])
	{
		$stmt = $this->conn->executeQuery($sql, $params, $types);
		return $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);
	}
	
	/**
	 * escape column/table names for reserved SQL words
	 * @param array|string $input
	 * @return array|string
	 * @throws Exception
	 */
	public function escapeSqlWords ($input)
	{
		if(!$input) {
			throw new Exception('Empty input');
		}
		
		$escapeFunction = function ($value) {
			return '`'.preg_replace('/[^A-Za-z0-9_]+/', '', $value).'`';
		};
		
		if (is_array($input)) {
			return array_map($escapeFunction, $input);
		} else {
			return $escapeFunction($input);
		}
	}
	
	/**
	 * dumps query with parameters in it
	 * @param QueryBuilder $query
	 */
	public static function dumpQuery(QueryBuilder $query)
	{
		$string = $query->getSQL();
		$data = $query->getParameters();

		$indexed = $data == array_values($data);
		foreach ($data as $k => $v) {
			if (is_string($v)) {
				$v = "'$v'";
			}
			if (is_array($v)) {
				$v = "'".implode("','", $v)."'";
			}
			if ($indexed) {
				$string = preg_replace('/\?/', $v, $string, 1);
			} else {
				$string = str_replace(":$k", $v, $string);
			}
		}

		dump($string);
	}
}
