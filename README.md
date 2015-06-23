
DBALManager
===========

This class is a helper for Doctrine DBAL. It has been made maily to ease managing bulk imports. It provides a method to execute `INSERT ... ON DUPLICATE KEY UPDATE` query on MySQL-compatible databases, which is what I miss in Doctrine's MySQL driver.


Symfony2 installation:
----------------------

To use this class in Symfony2, please look at [DBALManagerBundle](https://github.com/JarJak/DBALManagerBundle)


Integration with other frameworks:
----------------------------------

Add this in your composer.json:
```
"require": {
	"jarjak/dbal-manager": "dev-master"
},
"repositories": [
	{
		"type": "git",
		"url": "https://github.com/JarJak/DBALManager"
	}
]
```

The class is PSR-0 compatible, so it can be integrated easily with any modern framework.
Here is an example for Silex:

```php
//Application.php

$app['dbal_manager'] = $app->share(function ($app) {
    $manager = new JarJak\DBALManager();
	$manager->setConnection($app['db']);
	return $manager;
});
```

Example usage:
--------------

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

/* @var $DBALManager JarJak\DBALManager */
$DBALManager->insertOrUpdateByArray('user', $sqlArray, 2, ['active']);
```
