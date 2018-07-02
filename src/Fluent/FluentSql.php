<?php

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

interface FluentSql
{

	/**
	 * @param string|Fluent|Db\Query $from
	 * @param string|NULL $alias
	 * @return Fluent
	 */
	function table($from, ?string $alias = NULL): Fluent;


	function select(array $columns): Fluent;


	function distinct(): Fluent;


	/**
	 * @param string|Fluent|Db\Query $from
	 * @param string|NULL $alias
	 * @return Fluent
	 */
	function from($from, ?string $alias = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function join($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 */
	function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent;


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @return Fluent
	 */
	function crossJoin($join, ?string $alias = NULL): Fluent;


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return Fluent
	 */
	function on(string $alias, $condition): Fluent;


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return Fluent
	 */
	function where($condition, ...$params): Fluent;


	function whereAnd(array $conditions = []): Complex;


	function whereOr(array $conditions = []): Complex;


	function groupBy(array $columns): Fluent;


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return Fluent
	 */
	function having($condition, ...$params): Fluent;


	function havingAnd(array $conditions = []): Complex;


	function havingOr(array $conditions = []): Complex;


	function orderBy(array $colums): Fluent;


	function limit(int $limit): Fluent;


	function offset(int $offset): Fluent;


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	function union($query): Fluent;


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	function unionAll($query): Fluent;


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	function intersect($query): Fluent;


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	function except($query): Fluent;


	function insert(?string $into = NULL, ?array $columns = []): Fluent;


	function values(array $data): Fluent;


	function rows(array $rows): Fluent;


	function update(?string $table = NULL, ?string $alias = NULL): Fluent;


	function set(array $data): Fluent;


	function delete(?string $from = NULL, ?string $alias = NULL): Fluent;


	function returning(array $returning): Fluent;


	function truncate(?string $table = NULL): Fluent;

}
