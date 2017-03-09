<?php

namespace JarJak\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use JarJak\SqlDumper;
use PHPUnit\Framework\TestCase;

/**
 * @package DBALManager
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlDumperTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $conn;

    protected function setUp()
    {
        $this->conn = $this->createMock(Connection::class);
    }

    public function testGetQuery()
    {
        $qb = $this->getDumbQueryBuilder();
        $res = SqlDumper::getQuery($qb);
        $expected = "SELECT * FROM dumb_table WHERE id = 1";
        $this->assertEquals($expected, $res);
    }

    public function testGetSqlWithParams()
    {
        $sql = "SELECT * FROM dumb_table WHERE id = :id";
        $params = ['id' => 2];
        $res = SqlDumper::getSqlWithParams($sql, $params);
        $expected = "SELECT * FROM dumb_table WHERE id = 2";
        $this->assertEquals($expected, $res);
    }

    private function getDumbQueryBuilder()
    {
        $qb = new QueryBuilder($this->conn);
        $qb->select('*')
            ->from('dumb_table')
            ->where('id = ?')
            ->setParameters([1]);

        return $qb;
    }
}
