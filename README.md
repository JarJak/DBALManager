DBALManager
===========

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/7d2b23a4-1c67-46d6-9ac2-9af69041f3cf/small.png)](https://insight.sensiolabs.com/projects/7d2b23a4-1c67-46d6-9ac2-9af69041f3cf)

Set of helper classes for Doctrine DBAL. It has been made maily to ease creating bulk imports. It provides a method to execute `INSERT ... ON DUPLICATE KEY UPDATE` query on MySQL-compatible databases, which is what I miss in Doctrine's MySQL driver.


Symfony installation
--------------------

To use this class in Symfony 2/3, please look at [DBALManagerBundle](https://github.com/JarJak/DBALManagerBundle)


Integration with other frameworks
---------------------------------

Run:

```
composer require jarjak/dbal-manager
```

The class is PSR-0/PSR-4 compatible, so it can be integrated easily with any modern framework.
Here is an example for Silex:

```php
//Application.php

$app['dbal_manager'] = $app->share(function ($app) {
    $manager = new JarJak\DBALManager();
    $manager->setConnection($app['db']);
    return $manager;
});
```

Example usage
-------------

Lets say we have user table with: 
- unique usernames and emails
- column active can contain only 0 or 1 (not nullable)
- column address can be null

```php
$sqlArray = [
    'username' => 'JohnKennedy',
    'email' => 'john@kennedy.gov',
    'password' => $password,
    'address' => '',
    'active' => 0,
];

/* @var $manager JarJak\DBALManager */
$manager->insertOrUpdateByArray('user', $sqlArray, 2, ['active']);
```

Dumping Queries
---------------

DBALManager can use VarDumper to dump SQL queries from QueryBuilder ready to be copypasted into database server (with parameters already included).

```php
/* @var QueryBuilder $queryBuilder */
\JarJak\SqlDumper::dumpQuery($queryBuilder);
```

If you don't use QueryBuilder you can still dump parametrized SQL with:

```php
\JarJak\SqlDumper::dumpSql($sql, $params);
```

Testing
-------

Run tests with:

```
bin/vender/phpunit -v
```
