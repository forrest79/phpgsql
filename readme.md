PhPgSql
=======

[![License](https://img.shields.io/badge/License-BSD%203--Clause-blue.svg)](https://github.com/forrest79/PhPgSql/blob/master/license.md)
[![Build Status](https://travis-ci.org/forrest79/PhPgSql.svg?branch=master)](https://travis-ci.org/forrest79/PhPgSql)

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

```sh
composer require forrest79/phpgsql --dev
```

PhPgSql requires PHP 7.1.0 and pgsql binary extension. It doesn't work with PDO!


### Data type converting

This library automatically convert PG types to PHP types. Simple types are converted by `BasicDataTypeParser`, you can extends this parser or write your own, if you need parse another types or if you want to change parsing behavior.

**Important!** To determine PG types from PG result is by default used function `pg_field_type`. This function has one undocumented behavior, sends SQL query `select oid,typname from pg_type` (https://github.com/php/php-src/blob/master/ext/pgsql/pgsql.c) for every request to get proper type names. This SELECT is relatively fast and parsing works out of the box with this. But for bigger databases can be this SELECT slower and in common, there is no need to perform it for all requests. We can cache this data and then use function `pg_field_type_oid`. Cache is needed to flush only if database structure is changed. You can use simply cache for this and this is recommended way. Options are, prepare your own cache with `DataTypesCache` interface or use one already prepared, this save cache to PHP file (it's really fast especially with opcache):

```php
$phpFileCache = new PhPgSql\Db\DataTypeCache\PhpFile('/tmp/cache'); // we need connection to load data from DB and each connection can has different data types

$connection = new PhPgSql\Db\Connection();
$connection->setDataTypeCache($phpFileCache);

// when database structure has changed:
$phpFileCache->clean();
```


### Using

First, create connection to PostgreSQL and connect it:

```php
$connection = new Db\Connection('host=sheep port=5432 dbname=test user=lamb password=bar connect_timeout=5'); // good habit is to use connect_timeout parameter 
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
$result = $connection->queryArgs('SELECT column FROM table WHERE id = ? AND year > ?', [1, 2000]);
```

We can you `?` for param, this work automatically and we can use some special things as pass array, literal, bool or another query. We can also use classic `$1`, `$2`, ..., but with this, no special features is available and you can't combine `?` and `$1`.

If you want to pass char `?`, just escape it with `\` like this `\?`. This is only one magic thing we need know.

Passed variable can be scalar, array (is rewriten to many ?, ?, ?, ...), literal (is pass to SQL as string, never pass with this user input, possible SQL-injection), bool (PHP PostgreSQL prepared statement can't handle PHP `TRUE`\`FALSE`) and another query. To pass another query, we need to prepare one:

```php
$query = Db\Sql\Query::create('SELECT id FROM table WHERE year > ?', 2000);
$query = Db\Sql\Query::createArgs('SELECT id FROM table WHERE year > ?', [2000]);
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

And for `INSERT`/`UPDATE`/`DELETE` results we can get number of affected rows:

```php
$row = $result->getAffectedRows();
```

Finally, we can free result:

```php
$result->free();
```

We can also run query asynchronously. Just use this (syntax is the same as query and queryArgs):

```php
$result = $connection->asyncQuery('SELECT * FROM table WHERE id = ?', 1);
$result = $connection->asyncQueryArgs('SELECT * FROM table WHERE id = ?', [1]);
```

You can run just one async query on connection (but you can run more queries separated with `;` at once in one function call - but only when you don't use parameters - this is `pgsql` extension limitations), before we can run new async query, we need to get results. When you pass more queries in one function call, you need to call this for every query in call. Results are getting in the same order as queries are passed to the function.

```php
$connection->getNextAsyncQueryResult();
```

If you want to run simple SQL query/queries (separated with `;`) without parameters and you don't care about results, you can use `execute(string $sql)` function or `asyncExecute(string $sql)` (call `completeAsyncExecute()` to be sure that all async queries were completed).

> If you use `query()` or `asyncQuery()` without parameters, you can also pass more queries separated with `;`, but you will get only last result for non-async variant. Internally - `execute()` and `query()/asyncQuery()` without parameters call the same `pg_*` functions.  

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

PostgreSQL can raise a notice. This is very handy for development purposes. Notices can be read with `$connection->getNotices(bool $clearAfterRead = TRUE)`. You can call this function after query or at the end of the PHP script.

## Fluent

### Common use

Fluent interface can be used to simply create SQL queries using PHP.

We can start with ```Fluent\Query``` object:

```php
$fluent = new Fluent\Query();
$fluent->select(['*'])->createSqlQuery(); // create Query object with SQL and params to pg_query_params
```

But if fluent object has no DB connection, you can't send query directly to database. You can pass connection as parameter in `create(Db\Connection $connection)` function or the better solution is to start with `Fluent\Connection`, which pass DB connection to `Fluent\Query` automaticaly:

```php
$fluent = new Fluent\Connection();
$rows = $fluent->select(['*'])->from('table')->fetchAll();
```

You can use all fetch functions as on `Db\Result`. If you create query that returns no data, you can run it with `execute()`, that return `Db\Result` object.

You can update your query till `execute()` is call, after that, no updates on query is available, you can only execute this query again by calling `reexecute()`:

```php
$fluent = (new Fluent\Connection())
	->select(['*'])
	->from('table');

$rows = $fluent->fetchAll();

$freshRows = $fluent->reexecute()->fetchAll();
```

You can start creating your query with every possible command, it does't matter on the order of commands, SQL is always created right. Every query is `SELECT` at first, until you call `->insert(...)`, `->update(...)`, `->delete(...)` or `->truncate(...)`, which change query to apropriate SQL command. So you can prepare you query in common way and at the end, you can decide if you want to `SELECT` data or `DELETE` data or whatsoever. If you call some command more than once, data is merged, for example, this `->select(['column1'])->select(['column2'])` is the same as `->select(['column1', 'column2'])`.

There is one special command ```->table(...)```, it define main table for SQL, when you call select, it will be used as FROM, if you call INSERT it will be used as INTO, the same for UPDATE, DELETE or TRUNCATE.

```php
$fluent = (new Fluent\Connection())
	->table('table', 't');

$fluent->select(['*']); // SELECT * FROM table AS t
// $fluent->value(['column' => 1]); // INSERT INTO table(column) VALUES($1);
// $fluent->set(['column' => 1]); // UPDATE table AS t SET column = $1;
```

Every table definition command (like `->table(...)`, `->from(...)`, joins, update table, ...) has table alias definition, you don't need to use this. If you want to create alias for column in select, use string key in array definition:

```php
(new Fluent\Connection())
	->select(['column1', 'alias' => 'column_with_alias']); // SELECT column1, column_with_alias AS alias
```

If you call more ```->where(...)``` or ```->having(...)``` it is concat with AND. You can create more sophisticated conditions with ```Complex``` object.

```php
(new Fluent\Connection())
	->whereOr(); // add new OR (return Complex object)
		->add('column', 1) // this is add to OR
		->add('column2', [2, 3]) // this is also add to OR
		->addComplexAnd() // this is also add to OR and can contains more ANDs
			->add('column', $this->fluent()->select([1])) // this is add to AND
			->add('column2 = ANY(?)', new Db\Query('SELECT 2')) // this is add to AND
		->parent() // get original OR
		->add('column3 IS NOT NULL') // and add to OR new condition
	->fluent() // back to original fluent object
	->select(['*'])
	->from('table')
	->createSqlQuery()
    ->createQuery() // 'SELECT * FROM table WHERE column = $1 OR column2 IN ($2, $3) OR (column IN (SELECT 1) AND column2 = ANY(SELECT 2)) OR column3 IS NOT NULL'
```

The same can be used with ```HAVING``` and ```ON``` conditions for joins, but ```ON``` conditions don't have this API. You have to pass it manually:

```php
(new Fluent\Connection())
	->join('table', 't' /*, here could be the same as in the second argument of 'on' function */)
	// all these ons will be merged to one conditions - 't' is alias if is used or 'table' if there is no alias
	->on('t', 't.id = c.table_id') // most conditions are this simple, so you can pass simple string
	->on('t', ['t.id IN (?)', [1, 2, 3]]) // if you want to use dynamic parameters in condition, use ? in string and add param to array, where first value is condition string
	->on('t', [['t.id = c.table_id'], ['t.id = ?', 1]]) // you can pass more conditions and it will be concat with AND, in this case, every condition must be array, even if there is only one item as condition string (we can't recognize, if second argument will be new condition on string param to first condition)
	->on('t', $complex) // you can pass prepared Complex object, Complex::createAnd(...)/creatrOr(...)
```

Every condition (in `WHERE`/`HAVING`/`ON`) can be simple string, can have one argument with `=`/`IN` detection or can have many argument with `?` character:

```php
(new Fluent\Connection())
	->where('column IS NOT NULL');
	->where('column', $value); // in value is scalar = ? will be add, if array, Db\Query or other Fluent IN (?) will be added
	->where('column BETWEEN ? AND ?', $from, $to) // you need pass as many argument as ? is passed
```

To almost every parameters (select, where, having, on, orderBy, returning, from, joins, unions, ...) you can pass ```Db\Sql\Query``` (`Db\Sql` interface) or other ```Fluent\Query``` object. At some places (select, from, joins), you must provide alias if you want to pass this objects.

```php
$fluent = (new Fluent\Connection())
	->select(['column'])
	->from('table')
	->limit(1);

(new Fluent\Connection())
	->select(['c' => $fluent])

(new Fluent\Connection())
	->from($fluent, 'c')

(new Fluent\Connection())
	->join($fluent, 'c')

(new Fluent\Connection())
	->where('id IN (?)', $flunt)

(new Fluent\Connection())
	->union($flunt)
```

### Inserts

You can insert simple row:

```php
(new Fluent\Connection())
	->insert('table')
	->values([
		'column' => 1
	])
	->execute(); // or ->getAffectedRows()
```

Or you can use returning statement:

```php
$insertedData = (new Fluent\Connection())
	->insert('table')
	->values([
		'column' => 1
	])
	->returning(['column'])
	->fetch();
```

If you want, you can use multi-insert too:

```php
(new Fluent\Connection())
	->insert('table')
	->rows([
		['column' => 1],
		['column' => 2],
		['column' => 3],
	])
	->execute();
```

Here is column names detected from the first value or you can pass them as second parametr in ```insert()```:

```php
(new Fluent\Connection())
	->insert('table', ['id', 'name'])
	->rows([
		[1, 'Jan'],
		[2, 'Ondra'],
		[3, 'Petr'],
	])
	->execute();
```

And of course, you can use `INSERT` - `SELECT`:

```php
(new Fluent\Connection())
	->insert('table', ['name'])
	->select(['column'])
	->from('table2')
	->execute(); // INSERT INTO table(name) SELECT column FROM table2
```

And if you're using the same names for columns in `INSERT` and `SELECT`, you can call insert without columns list and it will be detected from select columns.

```php
(new Fluent\Connection())
	->insert('table')
	->select(['column'])
	->from('table2')
	->execute(); // INSERT INTO table(column) SELECT column FROM table2
```

### Update

You can use simple update:

```php
(new Fluent\Connection())
	->update('table')
	->set([
		'column' => 1,
	])
	->where('column', 100)
	->execute();
```

Or complex with from (and joins, ...):

```php
(new Fluent\Connection())
	->update('table', 't')
	->set([
		'column' => 1,
		'column_from' => Db\Literal::create('t2.id')
	])
	->from('table2', 't2')
	->where('t2.column', 100)
	->execute();
```

### Delete

Is similar to select, just call ```->delete()```.

### Truncate

Just with table name:

```php
(new Fluent\Connection())
	->truncate('table')
	->execute();

(new Fluent\Connection())
	->table('table')
	->truncate()
	->execute();
```
