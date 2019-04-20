<?php

declare(strict_types=1);

namespace JarJak\Tests;

use Doctrine\DBAL\Connection;
use JarJak\DBALManager;
use PHPUnit\Framework\TestCase;

/**
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class DBALManagerTest extends TestCase
{
    /**
     * @var Connection
     */
    protected $conn;

    protected function setUp(): void
    {
        $this->conn = $this->createMock(Connection::class);
        $this->conn->method('executeUpdate')->willReturn(1);
        $this->conn->method('lastInsertId')->willReturn(1);
    }

    public function testInsertOrUpdate(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->insertOrUpdate('dumb_table', ['dumb' => 'value']);
        $this->assertSame(1, $res);
    }

    public function testMultiInsertOrUpdate(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->multiInsertOrUpdate('dumb_table', [['dumb' => 'value'], ['dumb' => 'value']]);
        $this->assertSame(1, $res);
    }

    public function testMultiInsertOrUpdateReturnArray(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->multiInsertOrUpdate(
            'dumb_table',
            [['dumb' => 'value'], ['dumb' => 'value']],
            0,
            null,
            true
        );
        $this->assertSame([
            'inserted' => 1,
            'updated' => 1,
        ], $res);
    }

    public function testInsertIgnore(): void
    {
        $res = $this->getDumbDbalManager()->insertIgnore('dumb_table', ['dumb' => 'value'], []);
        $this->assertSame(1, $res);
    }

    private function getDumbDbalManager()
    {
        return new DBALManager($this->conn);
    }
}
