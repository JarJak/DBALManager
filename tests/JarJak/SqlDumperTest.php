<?php

declare(strict_types=1);

namespace JarJak\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use JarJak\SqlDumper;
use PHPUnit\Framework\TestCase;

/**
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlDumperTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(Connection::class);
    }

    public function testGetQuery(): void
    {
        $qb = $this->getDumbQueryBuilder();
        $res = SqlDumper::getQuery($qb);
        $expected = 'SELECT * FROM dumb_table WHERE id = 1';
        $this->assertSame($expected, $res);
    }

    public function testGetSqlWithParams(): void
    {
        $sql = 'SELECT * FROM dumb_table WHERE id = :id';
        $params = ['id' => 2];
        $res = SqlDumper::getSqlWithParams($sql, $params);
        $expected = 'SELECT * FROM dumb_table WHERE id = 2';
        $this->assertSame($expected, $res);
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
