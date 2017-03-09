<?php

namespace JarJak\Tests;

use JarJak\SqlPreparator;
use PHPUnit\Framework\TestCase;

/**
 * @package DBALManager
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlPreparatorMultiInsertOrUpdateTest extends TestCase
{
    public function testSimple()
    {
        $sql = "INSERT INTO test (a) VALUES (?) ON DUPLICATE KEY UPDATE a=VALUES(a)";
        $data = [['a' => 'av']];
        $res = SqlPreparator::prepareMultiInsertOrUpdate('test', $data);

        $this->assertEquals($sql, $res['sql']);
        $this->assertEquals(['av'], $res['params']);
    }

    public function testDouble()
    {
        $sql = "INSERT INTO test (a, b) VALUES (?,?), (?,?) ON DUPLICATE KEY UPDATE b=VALUES(b)";
        $data = [['a' => 'av', 'b' => 'bv'], ['a' => 'av', 'b' => 'bv']];
        $res = SqlPreparator::prepareMultiInsertOrUpdate('test', $data, ['a']);

        $this->assertEquals($sql, $res['sql']);
        $this->assertEquals(['av', 'bv', 'av', 'bv'], $res['params']);
    }

    public function testDoubleByFour()
    {
        $sql = "INSERT INTO test (a, b, c, d) VALUES (?,?,?,?), (?,?,?,?) ON DUPLICATE KEY UPDATE a=VALUES(a), b=VALUES(b), c=VALUES(c), d=VALUES(d)";
        $data = [['a' => 'av', 'b' => '0', 'c' => 'cv', 'd' => 'dv'], ['a' => 'av', 'b' => '0', 'c' => 'cv', 'd' => 'dv']];
        $res = SqlPreparator::prepareMultiInsertOrUpdate('test', $data);

        $this->assertEquals($sql, $res['sql']);
        $this->assertEquals(['av', '0', 'cv', 'dv', 'av', '0', 'cv', 'dv'], $res['params']);
    }

    public function testDoubleByFour2()
    {
        $sql = "INSERT INTO test (a, b, c, d) VALUES (?,?,?,?), (?,?,?,?) ON DUPLICATE KEY UPDATE a=VALUES(a), b=VALUES(b), d=VALUES(d)";
        $data = [['a' => 'av', 'b' => '', 'c' => 'cv', 'd' => 'dv'], ['a' => 'av', 'b' => '', 'c' => 'cv', 'd' => 'dv']];
        $res = SqlPreparator::prepareMultiInsertOrUpdate('test', $data, ['c']);

        $this->assertEquals($sql, $res['sql']);
        $this->assertEquals(['av', '', 'cv', 'dv', 'av', '', 'cv', 'dv'], $res['params']);
    }
}