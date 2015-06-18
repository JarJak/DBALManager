
DBALManager
===========

This class is a helper for Doctrine DBAL. It has been made maily to ease managing bulk imports. It provides a method to execute "INSERT ... ON DUPLICATE KEY UPDATE" query on MySQL-compatible databases, which is what I miss in Doctrine's MySQL driver.

Example usage:
--------------

Lets say we have user table with: 
-unique usernames and emails
-column active can contain only 0 or 1 (not nullable)
-column address can be null

```
$sqlArray = [
	'username' => 'JohnKennedy',
	'email' => 'john@kennedy.gov'
	'password' => $password,
	'address' => '',
	'active' => 0,
];

$DBALManager->insertOrUpdateByArray('user', $sqlArray, 2, ['active']);
```