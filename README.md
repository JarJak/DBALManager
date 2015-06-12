# DBALManager

This class is a helper for Doctrine DBAL. It has been made maily to ease managing bulk imports. It provides a method to execute "INSERT ... ON DUPLICATE KEY UPDATE" query on MySQL-compatible databases, which is what I miss in Doctrine's MySQL driver.
