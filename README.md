DBALManager
===========

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/26cdcbf9-dd47-452a-a933-f954ecd90d03/big.png)](https://insight.sensiolabs.com/projects/26cdcbf9-dd47-452a-a933-f954ecd90d03)
[![Build Status](https://travis-ci.org/JarJak/DBALManager.svg?branch=master)](https://travis-ci.org/JarJak/DBALManager)

Set of helper classes for Doctrine DBAL. It has been made maily to ease creating bulk imports. It provides a method to execute `INSERT ... ON DUPLICATE KEY UPDATE` query on MySQL-compatible databases, which is what I miss in Doctrine's MySQL driver.


Symfony installation
--------------------

To use this class in Symfony 2/3, please look at [DBALManagerBundle](https://github.com/JarJak/DBALManagerBundle).

In Symfony 4, thanks to autowiring you don't need a bundle, just add these lines in your `services.yaml`:
```yaml
services:
    _defaults:
        autowire: true
        
    JarJak\DBALManager: ~
```


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
    $manager = new JarJak\DBALManager($app['db']);
    return $manager;
});
```

Simple example:
---

You want to insert data or update them if row already exists.

```php
$sqlArray = [
	'id' => 1,
	'username' => 'JohnKennedy',
	'email' => 'john@kennedy.gov'
];

/* @var $manager JarJak\DBALManager */
$manager->insertOrUpdateByArray('user', $sqlArray);
```
Or you want to just skip this row if it exists:
```php
$manager->insertIgnoreByArray('user', $sqlArray);
```

Advanced example:
---

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
composer run-script test
```

Fix code style with:

```
composer run-script csfix
```
