<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

interface Sql
{

	function table(string|Query|Db\Sql $table, string|NULL $alias = NULL): Query;


	/**
	 * @param array<int|string, string|int|bool|Query|Db\Sql|NULL> $columns
	 */
	function select(array $columns): Query;


	function distinct(): Query;


	function distinctOn(string|Query|Db\Sql ...$on): Query;


	function from(string|Query|Db\Sql $from, string|NULL $alias = NULL): Query;


	function join(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function innerJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function leftJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function leftOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function rightJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function rightOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function fullJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function fullOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function crossJoin(string|Query|Db\Sql $join, string|NULL $alias = NULL): Query;


	function on(string $alias, string|Complex|Db\Sql $condition, mixed ...$params): Query;


	function lateral(string $alias): Query;


	function where(string|Complex|Db\Sql $condition, mixed ...$params): Query;


	function whereIf(bool $ifCondition, string|Complex|Db\Sql $condition, mixed ...$params): Query;


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 */
	function whereAnd(array $conditions = []): Complex;


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 */
	function whereOr(array $conditions = []): Complex;


	function groupBy(string ...$columns): Query;


	function having(string|Complex|Db\Sql $condition, mixed ...$params): Query;


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 */
	function havingAnd(array $conditions = []): Complex;


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 */
	function havingOr(array $conditions = []): Complex;


	function orderBy(string|Query|Db\Sql ...$columns): Query;


	function limit(int $limit): Query;


	function offset(int $offset): Query;


	function union(string|Query|Db\Sql $query): Query;


	function unionAll(string|Query|Db\Sql $query): Query;


	function intersect(string|Query|Db\Sql $query): Query;


	function except(string|Query|Db\Sql $query): Query;


	/**
	 * @param list<string>|NULL $columns
	 */
	function insert(string|NULL $into = NULL, string|NULL $alias = NULL, array|NULL $columns = []): Query;


	/**
	 * @param array<string, mixed> $data
	 */
	function values(array $data): Query;


	/**
	 * @param list<array<string, mixed>> $rows
	 */
	function rows(array $rows): Query;


	/**
	 * @param string|list<string>|NULL $columnsOrConstraint
	 */
	function onConflict(string|array|NULL $columnsOrConstraint = NULL, string|Complex|Db\Sql|NULL $where = NULL): Query;


	/**
	 * @param array<int|string, string|Db\Sql> $set
	 */
	function doUpdate(array $set, string|Complex|Db\Sql|NULL $where = NULL): Query;


	function doNothing(): Query;


	function update(string|NULL $table = NULL, string|NULL $alias = NULL): Query;


	/**
	 * @param array<string, mixed> $data
	 */
	function set(array $data): Query;


	function delete(string|NULL $from = NULL, string|NULL $alias = NULL): Query;


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $returning
	 */
	function returning(array $returning): Query;


	function merge(string|NULL $into = NULL, string|NULL $alias = NULL): Query;


	function using(
		string|Query|Db\Sql $dataSource,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): Query;


	function whenMatched(string|Db\Sql $then, string|Complex|Db\Sql|NULL $condition = NULL): Query;


	function whenNotMatched(string|Db\Sql $then, string|Complex|Db\Sql|NULL $condition = NULL): Query;


	function truncate(string|NULL $table = NULL): Query;


	function with(
		string $as,
		string|Query|Db\Sql $query,
		string|NULL $suffix = NULL,
		bool $notMaterialized = FALSE,
	): Query;


	function recursive(): Query;


	function prefix(string $queryPrefix, mixed ...$params): Query;


	function suffix(string $querySuffix, mixed ...$params): Query;

}
