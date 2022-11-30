

Passed variable can be scalar, `array` (is rewriten to many `?`, `?`, `?`, ... - this is usefull for example for `column IN (?)`), literal (is passed to SQL as string, never pass with this user input, possible SQL-injection), `bool`, `NULL` or another query (object implementing `Db\Sql` interface - there are some already prepared).

> If you have an array with a many items, consider usign `ANY` with just one parametr as PostgreSQL array instead of `IN` with many params:

```php
$ids = [1, 2, 4]; 

$resultIn = $connection->query('SELECT id, name FROM departments WHERE id IN (?)', $ids);

// this will generate a query with 3 parameters...

$rowsIn = $resultIn->fetchAll();

table($rowsIn);
/**
------------------------------------
| id          | name               |
|==================================|
| (integer) 1 | (string) 'IT'      |
| (integer) 2 | (string) 'HR'      |
| (integer) 4 | (string) 'Drivers' |
------------------------------------
*/

$resultAny = $connection->query('SELECT id, name FROM departments WHERE id = ANY(?)', Forrest79\PhPgSql\Db\Helper::createPgArray($ids));

// this will generate a query with just one parameter...

$rowsAny = $resultIn->fetchAll();

table($rowsAny);
/**
------------------------------------
| id          | name               |
|==================================|
| (integer) 1 | (string) 'IT'      |
| (integer) 2 | (string) 'HR'      |
| (integer) 4 | (string) 'Drivers' |
------------------------------------
*/

```

To pass another query, we need to prepare one and then use it:

```php

```

We get an `array` with the column names as key and `TRUE`/`FALSE` as a value. `TRUE` means, that these columns were accessed in the application. When `NULL` is returned, it means, that no column was accessed. This could be for example for `INSERT` queries or even for `SELECT` queries if no column was accessed.

And for `INSERT`/`UPDATE`/`DELETE` results we can get number of affected rows with the `getAffectedRows()` method:

```

We need to pass parameters not as concatenating strings but separated from a query - we have SQL query with placeholders for parameters and list of parameters. In this case DB can fail on this query, because `$userId` is not valid integer, and it can't be used in condition with the `id` column.

In this library, there are two possible ways how to do this. Use `?` characted for a parameter. This works automatically, and we can use some special functionallity as passing arrays, literals, bools, null or another queries. We can also use classic parameters `$1`, `$2`, ..., but with this, no special features are available, and imporatant is, you can't combine `?` with the `$1` syntax.

#### Literals, expressions and using queries in query

Sometimes you need to pass as a parameter piece of some SQL code. For these situations, there are prepared objects implementing `Forrest79\PhPgSql\Db\Sql` interface. This object can ba passed to a `?` in a query. Every object implementing this interface is pass to the query as is (be carefour, this could perform an SQL injection). These objects include a SQL string part, and you can use also parameters defined with a `?` character in the SQL part (when you use these parameters, they are pass safely to the final query and no SQL injection is performed here).

> These objects can be used also in fluent part of this library. You can use for example `Expression` as `SELECT` columns, so you can pass here securely some parameters (for example windows function, cases, ...). Other example - in `INSERT` or `UPDATE`, you can use `Literal` as inserted/updated value.

```

> There are some PostgreSQL types, that is hard to convert to PHP type (some types of arrays, hstore...) and this types can be simple converted to the JSON in a DB and this JSON can be simply converted in PHP. Parser throw an exception and give you a hint - convert type in SELECT to JSON. If you need parsing without converting to JSON, you need to write your own PHP logic (and you can create a pull-request for this :-)).

> Internal info: There is the interface `Forrest79\PhPgSql\Db\ColumnValueParser` that is required by the `Row` object. Main implemetation is in the `Result` object, that makes the real values parsing. Second is the `DummyColumnValueParser` that is used for manually created rows or for unserialized rows and this parser does nothing and just return a value.
