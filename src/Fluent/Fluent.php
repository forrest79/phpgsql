<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

interface Fluent
{

	/**
	 * @param string|self|Db\Sql $table
	 */
	function table($table, ?string $alias = NULL): self;


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $columns
	 */
	function select(array $columns): self;


	function distinct(): self;


	/**
	 * @param string|self|Db\Sql $from
	 */
	function from($from, ?string $alias = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function join($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 * @param string|Complex|Db\Sql|NULL $onCondition
	 */
	function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): self;


	/**
	 * @param string|self|Db\Sql $join table or query
	 */
	function crossJoin($join, ?string $alias = NULL): self;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function on(string $alias, $condition, ...$params): self;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function where($condition, ...$params): self;


	/**
	 * @param array<int, string|array|Db\Sql|Complex> $conditions
	 */
	function whereAnd(array $conditions = []): Complex;


	/**
	 * @param array<int, string|array|Db\Sql|Complex> $conditions
	 */
	function whereOr(array $conditions = []): Complex;


	function groupBy(string ...$columns): self;


	/**
	 * @param string|Complex|Db\Sql $condition
	 * @param mixed ...$params
	 */
	function having($condition, ...$params): self;


	/**
	 * @param array<int, string|array|Db\Sql|Complex> $conditions
	 */
	function havingAnd(array $conditions = []): Complex;


	/**
	 * @param array<int, string|array|Db\Sql|Complex> $conditions
	 */
	function havingOr(array $conditions = []): Complex;


	/**
	 * @param string|Query|Db\Sql ...$columns
	 */
	function orderBy(...$columns): self;


	function limit(int $limit): self;


	function offset(int $offset): self;


	/**
	 * @param string|self|Db\Sql $query
	 */
	function union($query): self;


	/**
	 * @param string|self|Db\Sql $query
	 */
	function unionAll($query): self;


	/**
	 * @param string|self|Db\Sql $query
	 */
	function intersect($query): self;


	/**
	 * @param string|self|Db\Sql $query
	 */
	function except($query): self;


	/**
	 * @param array<string>|NULL $columns
	 */
	function insert(?string $into = NULL, ?array $columns = []): self;


	/**
	 * @param array<string, mixed> $data
	 */
	function values(array $data): self;


	/**
	 * @param array<int, array<string, mixed>> $rows
	 */
	function rows(array $rows): self;


	function update(?string $table = NULL, ?string $alias = NULL): self;


	/**
	 * @param array<string, mixed> $data
	 */
	function set(array $data): self;


	function delete(?string $from = NULL, ?string $alias = NULL): self;


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $returning
	 */
	function returning(array $returning): self;


	function truncate(?string $table = NULL): self;


	/**
	 * @param mixed ...$params
	 */
	function prefix(string $queryPrefix, ...$params): self;


	/**
	 * @param mixed ...$params
	 */
	function sufix(string $querySufix, ...$params): self;

}
