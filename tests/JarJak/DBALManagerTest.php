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
    }

    public function testInsertOrUpdateByArray(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->insertOrUpdateByArray('dumb_table', ['dumb' => 'value']);
        $this->assertSame(0, $res);
    }

    public function testMultiInsertOrUpdateByArray(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->multiInsertOrUpdateByArray('dumb_table', [['dumb' => 'value'], ['dumb' => 'value']]);
        $this->assertSame(0, $res);
    }

    public function testMultiInsertOrUpdateByArrayReturnArray(): void
    {
        $dbal = $this->getDumbDbalManager();
        $res = $dbal->multiInsertOrUpdateByArray(
            'dumb_table',
            [['dumb' => 'value'], ['dumb' => 'value']],
            0,
            false,
            true
        );
        $this->assertSame([
            'inserted' => 0,
            'updated' => 0,
        ], $res);
    }

    public function testInsertIgnoreByArray(): void
    {
        $res = $this->getDumbDbalManager()->insertIgnoreByArray('dumb_table', ['dumb' => 'value'], []);
        $this->assertSame(0, $res);
    }

    private function getDumbDbalManager()
    {
        return new DBALManager($this->conn);
    }
}
