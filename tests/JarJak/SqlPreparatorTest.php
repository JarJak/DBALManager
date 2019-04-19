<?php

declare(strict_types=1);

namespace JarJak\Tests;

use JarJak\SqlPreparator;
use PHPUnit\Framework\TestCase;

/**
 * @author  Jarek Jakubowski <egger1991@gmail.com>
 */
class SqlPreparatorTest extends TestCase
{
    /**
     * @dataProvider insertOrUpdateDataProvider
     */
    public function testInsertOrUpdate($data, $sql, $params): void
    {
        $res = SqlPreparator::prepareInsertOrUpdate('test', $data);
        $this->assertSame($sql, $res['sql']);
        $this->assertSame($params, $res['params']);
    }

    public function insertOrUpdateDataProvider()
    {
        return [
            [
                'data' => ['a' => 'av'],
                'sql' => 'INSERT INTO `test` (`a`) VALUES (?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), id=LAST_INSERT_ID(id)',
                'params' => ['av'],
            ],
            [
                'data' => [
                    'a' => 'av',
                    'b' => 'bv',
                ],
                'sql' => 'INSERT INTO `test` (`a`, `b`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), `b`=VALUES(`b`), id=LAST_INSERT_ID(id)',
                'params' => ['av', 'bv'],
            ],
            [
                'data' => [
                    'a' => 'av',
                    'b' => '0',
                    'c' => '',
                    'd' => 'dv',
                ],
                'sql' => 'INSERT INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), `b`=VALUES(`b`), `c`=VALUES(`c`), `d`=VALUES(`d`), id=LAST_INSERT_ID(id)',
                'params' => ['av', '0', '', 'dv'],
            ],
            [
                'data' => [
                    'user' => 'vuser',
                    'key' => 'vkey',
                    'order' => 'vorder',
                    'group' => 'vgroup',
                ],
                'sql' => 'INSERT INTO `test` (`user`, `key`, `order`, `group`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `user`=VALUES(`user`), `key`=VALUES(`key`), `order`=VALUES(`order`), `group`=VALUES(`group`), id=LAST_INSERT_ID(id)',
                'params' => ['vuser', 'vkey', 'vorder', 'vgroup'],
            ],
        ];
    }

    public function testInsertOrUpdateIgnoreColumn(): void
    {
        $data = [
            'a' => 'av',
            'b' => '0',
            'c' => 'cv',
            'd' => 'dv',
        ];
        $sql = 'INSERT INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), `d`=VALUES(`d`), id=LAST_INSERT_ID(id)';
        $res = SqlPreparator::prepareInsertOrUpdate('test', $data, ['b', 'c']);
        $this->assertSame($sql, $res['sql']);
    }

    /**
     * @dataProvider insertIgnoreDataProvider
     */
    public function testInsertIgnore($data, $sql, $params): void
    {
        $res = SqlPreparator::prepareInsertIgnore('test', $data);
        $this->assertSame($sql, $res['sql']);
        $this->assertSame($params, $res['params']);
    }

    public function insertIgnoreDataProvider()
    {
        return [
            [
                'data' => ['a' => 'av'],
                'sql' => 'INSERT IGNORE INTO `test` (`a`) VALUES (?)',
                'params' => ['av'],
            ],
            [
                'data' => [
                    'a' => 'av',
                    'b' => 'bv',
                ],
                'sql' => 'INSERT IGNORE INTO `test` (`a`, `b`) VALUES (?, ?)',
                'params' => ['av', 'bv'],
            ],
            [
                'data' => [
                    'a' => 'av',
                    'b' => '0',
                    'c' => '',
                    'd' => 'dv',
                ],
                'sql' => 'INSERT IGNORE INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?)',
                'params' => ['av', '0', '', 'dv'],
            ],
            [
                'data' => [
                    'user' => 'vuser',
                    'key' => 'vkey',
                    'order' => 'vorder',
                    'group' => 'vgroup',
                ],
                'sql' => 'INSERT IGNORE INTO `test` (`user`, `key`, `order`, `group`) VALUES (?, ?, ?, ?)',
                'params' => ['vuser', 'vkey', 'vorder', 'vgroup'],
            ],
        ];
    }

    /**
     * @dataProvider multiInsertOrUpdateDataProvider
     */
    public function testMultiInsertOrUpdate($data, $sql, $params): void
    {
        $res = SqlPreparator::prepareMultiInsertOrUpdate('test', $data);
        $this->assertSame($sql, $res['sql']);
        $this->assertSame($params, $res['params']);
    }

    public function multiInsertOrUpdateDataProvider()
    {
        return [
            [
                'data' => [['a' => 'av']],
                'sql' => 'INSERT INTO `test` (`a`) VALUES (?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`)',
                'params' => ['av'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => 'bv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                ]],
                'sql' => 'INSERT INTO `test` (`a`, `b`) VALUES (?, ?), (?, ?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), `b`=VALUES(`b`)',
                'params' => ['av', 'bv', 'av', 'bv'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => '0',
                    'c' => 'cv',
                    'd' => 'dv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                    'c' => '',
                    'd' => 'dv',
                ]],
                'sql' => 'INSERT INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?), (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `a`=VALUES(`a`), `b`=VALUES(`b`), `c`=VALUES(`c`), `d`=VALUES(`d`)',
                'params' => ['av', '0', 'cv', 'dv', 'av', 'bv', '', 'dv'],
            ],
            [
                'data' => [[
                    'user' => 'vuser',
                    'key' => 'vkey',
                    'order' => 'vorder',
                    'group' => 'vgroup',
                ]],
                'sql' => 'INSERT INTO `test` (`user`, `key`, `order`, `group`) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE `user`=VALUES(`user`), `key`=VALUES(`key`), `order`=VALUES(`order`), `group`=VALUES(`group`)',
                'params' => ['vuser', 'vkey', 'vorder', 'vgroup'],
            ],
        ];
    }

    /**
     * @dataProvider multiInsertDataProvider
     */
    public function testMultiInsert($data, $sql, $params): void
    {
        $res = SqlPreparator::prepareMultiInsert('test', $data);
        $this->assertSame($sql, $res['sql']);
        $this->assertSame($params, $res['params']);
    }

    public function multiInsertDataProvider()
    {
        return [
            [
                'data' => [['a' => 'av']],
                'sql' => 'INSERT INTO `test` (`a`) VALUES (?)',
                'params' => ['av'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => 'bv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                ]],
                'sql' => 'INSERT INTO `test` (`a`, `b`) VALUES (?, ?), (?, ?)',
                'params' => ['av', 'bv', 'av', 'bv'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => '0',
                    'c' => 'cv',
                    'd' => 'dv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                    'c' => '',
                    'd' => 'dv',
                ]],
                'sql' => 'INSERT INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?), (?, ?, ?, ?)',
                'params' => ['av', '0', 'cv', 'dv', 'av', 'bv', '', 'dv'],
            ],
            [
                'data' => [[
                    'user' => 'vuser',
                    'key' => 'vkey',
                    'order' => 'vorder',
                    'group' => 'vgroup',
                ]],
                'sql' => 'INSERT INTO `test` (`user`, `key`, `order`, `group`) VALUES (?, ?, ?, ?)',
                'params' => ['vuser', 'vkey', 'vorder', 'vgroup'],
            ],
        ];
    }

    /**
     * @dataProvider multiInsertIgnoreDataProvider
     */
    public function testMultiInsertIgnore($data, $sql, $params): void
    {
        $res = SqlPreparator::prepareMultiInsertIgnore('test', $data);
        $this->assertSame($sql, $res['sql']);
        $this->assertSame($params, $res['params']);
    }

    public function multiInsertIgnoreDataProvider()
    {
        return [
            [
                'data' => [['a' => 'av']],
                'sql' => 'INSERT IGNORE INTO `test` (`a`) VALUES (?)',
                'params' => ['av'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => 'bv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                ]],
                'sql' => 'INSERT IGNORE INTO `test` (`a`, `b`) VALUES (?, ?), (?, ?)',
                'params' => ['av', 'bv', 'av', 'bv'],
            ],
            [
                'data' => [[
                    'a' => 'av',
                    'b' => '0',
                    'c' => 'cv',
                    'd' => 'dv',
                ], [
                    'a' => 'av',
                    'b' => 'bv',
                    'c' => '',
                    'd' => 'dv',
                ]],
                'sql' => 'INSERT IGNORE INTO `test` (`a`, `b`, `c`, `d`) VALUES (?, ?, ?, ?), (?, ?, ?, ?)',
                'params' => ['av', '0', 'cv', 'dv', 'av', 'bv', '', 'dv'],
            ],
            [
                'data' => [[
                    'user' => 'vuser',
                    'key' => 'vkey',
                    'order' => 'vorder',
                    'group' => 'vgroup',
                ]],
                'sql' => 'INSERT IGNORE INTO `test` (`user`, `key`, `order`, `group`) VALUES (?, ?, ?, ?)',
                'params' => ['vuser', 'vkey', 'vorder', 'vgroup'],
            ],
        ];
    }
}
