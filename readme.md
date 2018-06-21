PhPgSql
=======

## DB

### Introduction

Simple and fast PHP database library for PostgreSQL with auto converting DB types to PHP.

- lightweight
- no magic
- no database structure reading
- automatically convert PG data types to PHP data types
- support async connect to DB and async query
- simple creating queries with prepared statement with `?` for variable instead of $1, $2 and so...
  - as variable you can pass scalar, bool, array, literal or other query


### Installation

The recommended way to install PhPgSql is through Composer:

TODO
```
composer require forrest79/phpgsql --dev
```

PhPgSql requires PHP 7.1.0 and pgsql binary extension. It doesn't work with PDO!


### Using

First, create connection to PostgreSQL and connect it:

```php
$connection = new Db\Connection('host=sheep port=5432 dbname=test user=lamb password=bar');
$connection->connect();
```

Format for connection string is the same as for [pg_connect](http://php.net/manual/en/function.pg-connect.php). Pass TRUE as second parameter for force new connection and third parameter as TRUE to connect asynchronously.

Than, we can run queries and fetch results:

```php
$result = $connection->query('SELECT column FROM table');
```

Or with some parameters

```php
$result = $connection->query('SELECT column FROM table WHERE id = ? AND year > ?', 1, 2000);
$result = $connection->queryArray('SELECT column FROM table WHERE id = ? AND year > ?', [1, 2000]);
```

We can you ? for param, this work automatically and we can use some special things as pass array, literal, bool or another query. We can also use classic $1, $2, ..., but with this, no special features is available and you can't combine ? and $1.

If you want to pass char '?', just escape it with \ like this \?. This is only one magic thing we need know.

We can pass as variable scalar, array (is rewriten to many ?, ?, ?, ...), literal (is pass to SQL as string, never pass with this user input, possible SQL-injection), bool (PHP PostgreSQL prepared statement can't handle PHP TRUE\FALSE) and another query. To pass another query, we need to prepare one:

```php
$query = $connection->prepareQuery('SELECT id FROM table WHERE year > ?', 2000);
$query = $connection->prepareQueryArray('SELECT id FROM table WHERE year > ?', [2000]);
```

And pass it:

```php
$result = $connection->query('SELECT column FROM table WHERE id IN (?) AND type IN (?) AND valid = ? ORDER BY ?', $query, [1, 3], TRUE, $connection::literal('position'));
// SELECT column FROM table WHERE id IN (SELECT id FROM table WHERE year > $1) AND type IN ($2, $3) AND valid = $4 ORDER BY position; [2000, 1, 3, 't']
```

Now we can fetch results:

```php
// get one row or NULL if there is no data - this can be run in cycle
$row = $result->fetch();

// same as fetch(), but get one first column data or NULL if there is no data - this also can be run in cycle
$row = $result->fetchSingle();

// get array of all rows
$row = $result->fetchAll();

// special syntax for creating structure from data
$row = $result->fetchAssoc('col1[]col2'); // $tree[$val1][$index][$val2] = {record}
$row = $result->fetchAssoc('col1|col2=col3'); // $tree[$val1][$val2] = val2

// get indexed array, key is first column, value is second column or you can choose columns manually
$row = $result->fetchPairs();
$row = $result->fetchPairs('id', 'name');

$count = $result->getRowCount(); // ->count() or count($result)
```

On results we can also get column type in PostgeSQL types or all column names:

```php
$row = $result->getColumnType('name');
$row = $result->getColumns();
```

And for INSERT/UPDATE/DELETE results we can get number of affected rows:

```php
$row = $result->getAffectedRows();
```

Finally, we can free result:

```php
$result->free();
```

We can also run query asynchronously. Just use this (syntax is the same as query and queryArray):

```php
$result = $connection->asyncQuery('SELECT * FROM table WHERE id = ?', 1);
$result = $connection->asyncQueryArray('SELECT * FROM table WHERE id = ?', [1]);
```

You can run just one async query on connection, before we can run new async query, we need to get results from the first one:

```php
$connection->waitForAsyncQuery();
```

After that, we can use `$result` as normal $result from normal query.

When we get some row, we can fetch columns:

```php
echo $row->column1;
echo $row['column1'];
$data = $row->toArray();
```

Ale data have the right PHP type. If some type is not able to be parsed, exception is thrown. You can write and use your own data type parser:

```php
$connection->setDataTypeParser(new MyOwnDataTypeParserWithDataTypeParserInterface);
```

Don't be afraid to use transaction:

```php
$connection->begin();
$connection->commit();
$connection->rollback();

$connection->begin('savepoint1');
$connection->commit('savepoint1');
$connection->rollback('savepoint1');
```

Or listen on events like connect/close/query:

```php
$connection->addOnConnect(function(Connection $connection) {
	echo 'connect...';
});
$connection->addOnClose(function(Connection $connection) {
	echo 'close...';
});
$connection->addOnQuery(function(Connection $connection, Query $query, ?float $time = NULL) { // $time === NULL for async queries
	echo 'close...';
});
```

## Fluent

### NOT SUPPORTED NOW (only important things)
- ```[ WITH [ RECURSIVE ] with_query [, ...] ]``` add something like ```->addPrefix('WITH RECURSIVE SELECT column')```?
- ```DELETE FROM ... USING``` using can be written by ```WHERE ... IN (...)```, is using necessary?
- ```INSERT INTO ... ON CONFLICT```

## TODO

- complete docs
- Travis tests
- nette - choose connection class (default Fluent) 
- license

- https://packagist.org/
- performance - test foreach vs. array_walk vs. array_map
