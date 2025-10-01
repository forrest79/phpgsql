# Upgrade


## V1 to V2


- `Forrest79\PhPgSql\Db\Query` has public properties `$sql` and `$params`, methods `getSql()` and `getParams()` are removed
- `Forrest79\PhPgSql\Db\RowFactory` method `createRow()` is renamed to `create()`
- `Forrest79\PhPgsql\Fluent\Query` method `free()` is removed
- `Forrest79\PhPgsql\Fluent\Query` implement `Forrest79\PhPgSql\Db\Sql` interface, method `createSqlQuery()` is removed, and you pass `Forrest79\PhPgsql\Fluent\Query` directly as a parameter without converting with `createSqlQuery()`
   - to convert `Forrest79\PhPgsql\Fluent\Query` to `Forrest79\PhPgSql\Db\Query` use `toDbQuery()` method (instead of old `->createSqlQuery()->createQuery()`) 
- `Forrest79\PhPgsql\Fluent\Complex` is now `Forrest79\PhPgsql\Fluent\Condition` and methods `addComplexAnd()/addComplexOr()` are now `addAndBranch()/addOrBranch()` 
- `Forrest79\PhPgsql\Fluent\Connection` has no longer shortcuts to the `Forrest79\PhPgsql\Fluent\Query` methods, use `createQuery()` method to create a `Forrest79\PhPgsql\Fluent\QueryExecute` from a `Forrest79\PhPgsql\Fluent\Connection`    
- iterating query `Db\Result` (returned with `fetchYxz()/execute()`) methods  or `Fluent\Query` is removed, always use `fetchIterator()` method
