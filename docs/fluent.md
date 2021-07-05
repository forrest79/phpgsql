# PhPgSql\Fluent

**@todo**
- Connection (Connection.php) + QueryExecution (QueryExecution.php) - how to use own Query
- Query (Query.php) - all, how to return your own Query
- Complex (Complex.php)
- QueryBuilder (QueryBuiler.php) - how to extend

## Common use

Fluent interface can be used to simply create SQL queries using PHP.

Fluent methods are defined in the `Forrest79\PhPgSql\Fluent\Sql` interface. There're 3 object implementing this interface. You can start your query in the same way from all these objects:

- `Forrest79\PhPgSql\Fluent\Query` - this is the basic object, that can generate queries (but can't execute them)
- `Forrest79\PhPgSql\Fluent\QueryExecute` - this is `Fluent\Query` object extension, that requires `Db\Connection` object and can execute queries in database
- `Forrest79\PhPgSql\Fluent\Connection` - this is `Db\Connection` extension that creates `Fluent\QueryExecute` object with the correct `Db\Connection` - you will be probably using this most

Both `Query` and `QueryExecute` needs `QueryBuilder` object. `Fluent\Connection` pass this object automatically.

Fluent generates `Db\Sql\Query` object with `?` as placeholders for parameters that is handled by `PhPgSql\Db` part. Object is created and used internally but you can create it manually, if you want, with the `Fluent\Query::createSqlQuery()` method. 

```php
$fluent = new Forrest79\PhPgSql\Fluent\Query(new Forrest79\PhPgSql\Fluent\QueryBuilder());

$query = $fluent
  ->select(['*'])
  ->from('users')
  ->where('id', 1)
  ->createSqlQuery();

dump($query->getSql()); // (string) 'SELECT * FROM users WHERE id = ?'
dump($query->getParams()); // (array) [1]
```

With the `QueryExecute` you can run this query in DB. This object has all `fetch*()` methods as the `Db\Result`.

```php
$fluent = new Forrest79\PhPgSql\Fluent\QueryExecute(new Forrest79\PhPgSql\Fluent\QueryBuilder(), $connection);

$row = $fluent
  ->select(['*'])
  ->from('users')
  ->where('id', 1)
  ->fetch();

dump($row); // (Row) ['id' => 1, 'nick' => 'Bob', 'inserted_datetime' => '2020-01-01 09:00:00', 'active' => TRUE, 'age' => 45, 'height_cm' => 178.2, 'phones' => [200300, 487412]]
```

But you don't want to do this so complicated. Use `Fluent\Connection` to create `QueryExecute` easily:

```php
$userNick = $connection
  ->select(['nick'])
  ->from('users')
  ->where('id', 2)
  ->fetchSingle();

dump($userNick); // (string) 'Brandon'
```

## Generating SQL with objects

This is list all methods you can use to generate query. This covers most of the defaults SQL commands. If there is something missing - you must write your query manually as a string (or you can extend `Fluent\Query` and `Fluent\QueryBuilder` with your functionality - more about this later). Many methods define alias, some needs it and can't be used without set one.

You can start query with whatever method you want. Methods only sets query properties and from there properties is generated final SQL query.

- `table($table, ?string $alias = NULL): Query` - create `Query` object with defined main table - this table can be used for selects, updates, inserts or deletes - you don't need to use concrete method to define table. `$table` can be simple `string` or other `Query` or `Db\Sql`.
- `select(array $columns): Query` - defines columns (array `key => column`) to `SELECT`. String array key is column alias. Column can be `string`, `int`, `bool`, `Query`, `Db\Sql` or `NULL`
- `distinct(): Query` - create `SELECT DISCTINCT`
- `from($from, ?string $alias = NULL): Query` - defines table for `SELECT` query. `$from` can be simple `string` or other `Query` or `Db\Sql`.
- `join($join, ?string $alias = NULL, $onCondition = NULL): Query` (or `innetJoin(...)`/`leftJoin(...)`/`leftOuterJoin(...)`/`rightJoin(...)`/`rightOuterJoin(...)`/`fullJoin(...)`/`fullOuterJoin(...)`) - join table or query. You must provide alias if you want to add more conditions to `ON`. `$join` can be simple string or other `Query` or `Db\Sql`. `$onCondition` can be simple string or other `Complex` or `Db\Sql`. `Db\Sql` can be used for some complex expression, where you need to use `?` and parameters. 
- `crossJoin($join, ?string $alias = NULL): Query` - defines cross join. `$join` can be simple string or other `Query` or `Db\Sql`. There is no `ON` condition.
- `on(string $alias, $condition, ...$params): Query` - defines new `ON` condition for joins. More `ON` conditions for one join is connected with `AND`. If `$condition` is `string`, you can use `?` and parameters in `$params`. Otherwise `$condition` can be `Complex` or `Db\Sql`.


/**
 * @param string|Complex|Db\Sql $condition
 * @param mixed ...$params
 */
function where($condition, ...$params): Query;


/**
 * @param array<int, string|array<mixed>|Db\Sql|Complex> $conditions
 */
function whereAnd(array $conditions = []): Complex;


/**
 * @param array<int, string|array<mixed>|Db\Sql|Complex> $conditions
 */
function whereOr(array $conditions = []): Complex;


function groupBy(string ...$columns): Query;


/**
 * @param string|Complex|Db\Sql $condition
 * @param mixed ...$params
 */
function having($condition, ...$params): Query;


/**
 * @param array<int, string|array<mixed>|Db\Sql|Complex> $conditions
 */
function havingAnd(array $conditions = []): Complex;


/**
 * @param array<int, string|array<mixed>|Db\Sql|Complex> $conditions
 */
function havingOr(array $conditions = []): Complex;


/**
 * @param string|Query|Db\Sql ...$columns
 */
function orderBy(...$columns): Query;


function limit(int $limit): Query;


function offset(int $offset): Query;


/**
 * @param string|Query|Db\Sql $query
 */
function union($query): Query;


/**
 * @param string|Query|Db\Sql $query
 */
function unionAll($query): Query;


/**
 * @param string|Query|Db\Sql $query
 */
function intersect($query): Query;


/**
 * @param string|Query|Db\Sql $query
 */
function except($query): Query;


/**
 * @param array<string>|NULL $columns
 */
function insert(?string $into = NULL, ?array $columns = []): Query;


/**
 * @param array<string, mixed> $data
 */
function values(array $data): Query;


/**
 * @param array<int, array<string, mixed>> $rows
 */
function rows(array $rows): Query;


function update(?string $table = NULL, ?string $alias = NULL): Query;


/**
 * @param array<string, mixed> $data
 */
function set(array $data): Query;


function delete(?string $from = NULL, ?string $alias = NULL): Query;


/**
 * @param array<int|string, string|int|Query|Db\Sql> $returning
 */
function returning(array $returning): Query;


function truncate(?string $table = NULL): Query;


/**
 * @param mixed ...$params
 */
function prefix(string $queryPrefix, ...$params): Query;


/**
 * @param mixed ...$params
 */
function sufix(string $querySufix, ...$params): Query;



If fluent object has no DB connection, you can't send query directly to database. You can pass connection as parameter in `create(Db\Connection $connection)` function or the better solution is to start with `Fluent\Connection`, which pass DB connection to `Fluent\Query` automaticaly:

```xphp
$fluent = new Fluent\Connection();
$rows = $fluent->select(['*'])->from('table')->fetchAll();
```

You can use all fetch functions as on `Db\Result`. If you create query that returns no data, you can run it with `execute()`, that return `Db\Result` object.

You can update your query till `execute()` is call, after that, no updates on query is available, you can only execute this query again by calling `reexecute()`:

```xphp
$fluent = (new Fluent\Connection())
	->select(['*'])
	->from('table');

$rows = $fluent->fetchAll();

$freshRows = $fluent->reexecute()->fetchAll();
```

You can start creating your query with every possible command, it does't matter on the order of commands, SQL is always created right. Every query is `SELECT` at first, until you call `->insert(...)`, `->update(...)`, `->delete(...)` or `->truncate(...)`, which change query to apropriate SQL command. So you can prepare you query in common way and at the end, you can decide if you want to `SELECT` data or `DELETE` data or whatsoever. If you call some command more than once, data is merged, for example, this `->select(['column1'])->select(['column2'])` is the same as `->select(['column1', 'column2'])`.

There is one special command ```->table(...)```, it define main table for SQL, when you call select, it will be used as FROM, if you call INSERT it will be used as INTO, the same for UPDATE, DELETE or TRUNCATE.

```xphp
$fluent = (new Fluent\Connection())
	->table('table', 't');

$fluent->select(['*']); // SELECT * FROM table AS t
// $fluent->value(['column' => 1]); // INSERT INTO table(column) VALUES($1);
// $fluent->set(['column' => 1]); // UPDATE table AS t SET column = $1;
```

Every table definition command (like `->table(...)`, `->from(...)`, joins, update table, ...) has table alias definition, you don't need to use this. If you want to create alias for column in select, use string key in array definition:

```xphp
(new Fluent\Connection())
	->select(['column1', 'alias' => 'column_with_alias']); // SELECT column1, column_with_alias AS alias
```

If you call more ```->where(...)``` or ```->having(...)``` it is concat with AND. You can create more sophisticated conditions with ```Complex``` object.

```xphp
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

```xphp
(new Fluent\Connection())
	->join('table', 't' /*, here could be the same as in the second argument of 'on' function */)
	// all these ons will be merged to one conditions - 't' is alias if is used or 'table' if there is no alias
	->on('t', 't.id = c.table_id') // most conditions are this simple, so you can pass simple string
	->on('t', ['t.id IN (?)', [1, 2, 3]]) // if you want to use dynamic parameters in condition, use ? in string and add param to array, where first value is condition string
	->on('t', [['t.id = c.table_id'], ['t.id = ?', 1]]) // you can pass more conditions and it will be concat with AND, in this case, every condition must be array, even if there is only one item as condition string (we can't recognize, if second argument will be new condition on string param to first condition)
	->on('t', $complex) // you can pass prepared Complex object, Complex::createAnd(...)/creatrOr(...)
```

Every condition (in `WHERE`/`HAVING`/`ON`) can be simple string, can have one argument with `=`/`IN` detection or can have many argument with `?` character:

```xphp
(new Fluent\Connection())
	->where('column IS NOT NULL');
	->where('column', $value); // in value is scalar = ? will be add, if array, Db\Query or other Fluent IN (?) will be added
	->where('column BETWEEN ? AND ?', $from, $to) // you need pass as many argument as ? is passed
```

To almost every parameters (select, where, having, on, orderBy, returning, from, joins, unions, ...) you can pass ```Db\Sql\Query``` (`Db\Sql` interface) or other ```Fluent\Query``` object. At some places (select, from, joins), you must provide alias if you want to pass this objects.

```xphp
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

If you want to create copy of existing fluent query, just use `clone`:

```xphp
$newQuery = clone $existingQuery;
```

If `$existingQuery` was alredy executed, copy is cloned with reset resutlt, so you can still update `$newQuery` and then execute it.

### Inserts

You can insert simple row:

```xphp
(new Fluent\Connection())
	->insert('table')
	->values([
		'column' => 1
	])
	->execute(); // or ->getAffectedRows()
```

Or you can use returning statement:

```xphp
$insertedData = (new Fluent\Connection())
	->insert('table')
	->values([
		'column' => 1
	])
	->returning(['column'])
	->fetch();
```

If you want, you can use multi-insert too:

```xphp
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

```xphp
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

```xphp
(new Fluent\Connection())
	->insert('table', ['name'])
	->select(['column'])
	->from('table2')
	->execute(); // INSERT INTO table(name) SELECT column FROM table2
```

And if you're using the same names for columns in `INSERT` and `SELECT`, you can call insert without columns list and it will be detected from select columns.

```xphp
(new Fluent\Connection())
	->insert('table')
	->select(['column'])
	->from('table2')
	->execute(); // INSERT INTO table(column) SELECT column FROM table2
```

### Update

You can use simple update:

```xphp
(new Fluent\Connection())
	->update('table')
	->set([
		'column' => 1,
	])
	->where('column', 100)
	->execute();
```

Or complex with from (and joins, ...):

```xphp
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

```xphp
(new Fluent\Connection())
	->truncate('table')
	->execute();

(new Fluent\Connection())
	->table('table')
	->truncate()
	->execute();
```
