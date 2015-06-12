<?php

namespace JarJak\DBALManager\Manager;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * universal helper class for DBAL insert/update operations
 * @package DoctrineToolsBundle
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
	 * @param mixed $excludedDefaultNullCols array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely
	 * @return int InsertId
	 * @link http://stackoverflow.com/questions/778534/mysql-on-duplicate-key-last-insert-id
	 */
	public function insertOrUpdateByArray($table, $array, $updateIgnoreCount = 1, $excludedDefaultNullCols = array('enabled', '`default`'))
	{
		$cols = array();
		$params = array();
		$marks = array();
		foreach ($array as $k => $v) {
			if (false !== $excludedDefaultNullCols) {
				if (!$v && !in_array($k, $excludedDefaultNullCols)) {
					$v = null;
				}
			}
			$cols[] = $k;
			$params[] = $v;
			$marks[] = '?';
		}

		$sql = "INSERT INTO " . $table . " (";
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
	 * @param mixed $excludedDefaultNullCols array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely
	 * @return int InsertId
	 */
	public function insertByArray($table, $array, $excludedDefaultNullCols = array('enabled', '`default`'))
	{
		$cols = array();
		$params = array();
		$marks = array();
		foreach ($array as $k => $v) {
			if (false !== $excludedDefaultNullCols) {
				if (!$v && !in_array($k, $excludedDefaultNullCols)) {
					$v = null;
				}
			}
			$cols[] = $k;
			$params[] = $v;
			$marks[] = '?';
		}

		$sql = "INSERT INTO " . $table . " (";
		$sql .= implode(', ', $cols);
		$sql .= ") VALUES (";
		$sql .= implode(', ', $marks);
		$sql .= ") ";

		$this->conn->executeQuery($sql, $params);
		return $this->conn->lastInsertId();
	}

	/**
	 * executes "INSERT IGNORE" sql statement by array of parameters
	 * @param string $table table name
	 * @param array $array values
	 * @param mixed $excludedDefaultNullCols array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely
	 * @return int InsertId
	 */
	public function insertIgnoreByArray($table, $array, $excludedDefaultNullCols = array('enabled', '`default`'))
	{
		$cols = array();
		$params = array();
		$marks = array();
		foreach ($array as $k => $v) {
			if (false !== $excludedDefaultNullCols) {
				if (!$v && !in_array($k, $excludedDefaultNullCols)) {
					$v = null;
				}
			}
			$cols[] = $k;
			$params[] = $v;
			$marks[] = '?';
		}

		$sql = "INSERT IGNORE INTO " . $table . " (";
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
	 * @param mixed $excludedDefaultNullCols array of columns that can contain zero-equal values, set to false if you want to disable auto-null entirely
	 * @return int InsertId
	 */
	public function updateByArray($table, $array, $id, $excludedDefaultNullCols = array('enabled', '`default`'))
	{
		$cols = array();
		$params = array();
		$marks = array();

		foreach ($array as $k => $v) {
			if (false !== $excludedDefaultNullCols) {
				if (!$v && !in_array($k, $excludedDefaultNullCols)) {
					$v = null;
				}
			}
			$cols[] = $k;
			$params[] = $v;
			$marks[] = '?';
		}

		$sql = "UPDATE " . $table . " SET ";

		$updateArray = array();
		foreach ($cols as $col) {
			$updateArray[] = '`' . $col . '` = ?';
		}

		$sql .= implode(',', $updateArray);

		$sql .= 'WHERE id = ?';

		$params[] = $id;

		$this->conn->executeQuery($sql, $params);
		return $id;
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
