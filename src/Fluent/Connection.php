<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements Sql
{
	private QueryBuilder|NULL $queryBuilder = NULL;


	/**
	 * @throws Exceptions\QueryException
	 */
	public function table(string|Query|Db\Sql $table, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->table($table, $alias);
	}


	/**
	 * @param array<int|string, string|int|bool|Query|Db\Sql|NULL> $columns
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): QueryExecute
	{
		return $this->createQuery()->select($columns);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): QueryExecute
	{
		return $this->createQuery()->distinct();
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function from(string|Query|Db\Sql $from, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->from($from, $alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function join(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->join($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin(
		string|Query|Db\Sql $join,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin(string|Query|Db\Sql $join, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->crossJoin($join, $alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function lateral(string $alias): QueryExecute
	{
		return $this->createQuery()->lateral($alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, string|Complex|Db\Sql $condition, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->on($alias, $condition, ...$params);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function where(string|Complex|Db\Sql $condition, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->where($condition, ...$params);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whereIf(bool $ifCondition, string|Complex|Db\Sql $condition, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->whereIf($ifCondition, $condition, ...$params);
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->whereAnd($conditions);
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		return $this->createQuery()->whereOr($conditions);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function groupBy(string ...$columns): QueryExecute
	{
		return $this->createQuery()->groupBy(...$columns);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function having(string|Complex|Db\Sql $condition, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->having($condition, ...$params);
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->havingAnd($conditions);
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql|Complex> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		return $this->createQuery()->havingOr($conditions);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function orderBy(string|Query|Db\Sql ...$columns): QueryExecute
	{
		return $this->createQuery()->orderBy(...$columns);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): QueryExecute
	{
		return $this->createQuery()->limit($limit);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): QueryExecute
	{
		return $this->createQuery()->offset($offset);
	}


	public function union(string|Query|Db\Sql $query): QueryExecute
	{
		return $this->createQuery()->union($query);
	}


	public function unionAll(string|Query|Db\Sql $query): QueryExecute
	{
		return $this->createQuery()->unionAll($query);
	}


	public function intersect(string|Query|Db\Sql $query): QueryExecute
	{
		return $this->createQuery()->intersect($query);
	}


	public function except(string|Query|Db\Sql $query): QueryExecute
	{
		return $this->createQuery()->except($query);
	}


	/**
	 * @param list<string>|NULL $columns
	 * @throws Exceptions\QueryException
	 */
	public function insert(string|NULL $into = NULL, string|NULL $alias = NULL, array|NULL $columns = []): QueryExecute
	{
		return $this->createQuery()->insert($into, $alias, $columns);
	}


	/**
	 * @param array<string, mixed> $data
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): QueryExecute
	{
		return $this->createQuery()->values($data);
	}


	/**
	 * @param list<array<string, mixed>> $rows
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): QueryExecute
	{
		return $this->createQuery()->rows($rows);
	}


	/**
	 * @param string|list<string>|NULL $columnsOrConstraint
	 * @throws Exceptions\QueryException
	 */
	public function onConflict(
		string|array|NULL $columnsOrConstraint = NULL,
		string|Complex|Db\Sql|NULL $where = NULL,
	): QueryExecute
	{
		return $this->createQuery()->onConflict($columnsOrConstraint, $where);
	}


	/**
	 * @param array<int|string, string|Db\Sql> $set
	 * @throws Exceptions\QueryException
	 */
	public function doUpdate(array $set, string|Complex|Db\Sql|NULL $where = NULL): QueryExecute
	{
		return $this->createQuery()->doUpdate($set, $where);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function doNothing(): QueryExecute
	{
		return $this->createQuery()->doNothing();
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function update(string|NULL $table = NULL, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->update($table, $alias);
	}


	/**
	 * @param array<string, mixed> $data
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): QueryExecute
	{
		return $this->createQuery()->set($data);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function delete(string|NULL $from = NULL, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->delete($from, $alias);
	}


	/**
	 * @param array<int|string, string|int|Query|Db\Sql> $returning
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): QueryExecute
	{
		return $this->createQuery()->returning($returning);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function merge(string|NULL $into = NULL, string|NULL $alias = NULL): QueryExecute
	{
		return $this->createQuery()->merge($into, $alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function using(
		string|Query|Db\Sql $dataSource,
		string|NULL $alias = NULL,
		string|Complex|Db\Sql|NULL $onCondition = NULL,
	): QueryExecute
	{
		return $this->createQuery()->using($dataSource, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whenMatched(string|Db\Sql $then, string|Complex|Db\Sql|NULL $condition = NULL): QueryExecute
	{
		return $this->createQuery()->whenMatched($then, $condition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whenNotMatched(string|Db\Sql $then, string|Complex|Db\Sql|NULL $condition = NULL): QueryExecute
	{
		return $this->createQuery()->whenNotMatched($then, $condition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function truncate(string|NULL $table = NULL): QueryExecute
	{
		return $this->createQuery()->truncate($table);
	}


	public function with(
		string $as,
		string|Query|Db\Sql $query,
		string|NULL $suffix = NULL,
		bool $notMaterialized = FALSE,
	): QueryExecute
	{
		return $this->createQuery()->with($as, $query, $suffix, $notMaterialized);
	}


	public function recursive(): QueryExecute
	{
		return $this->createQuery()->recursive();
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->prefix($queryPrefix, ...$params);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function suffix(string $querySuffix, mixed ...$params): QueryExecute
	{
		return $this->createQuery()->suffix($querySuffix, ...$params);
	}


	public function setQueryBuilder(QueryBuilder $queryBuilder): static
	{
		$this->queryBuilder = $queryBuilder;

		return $this;
	}


	protected function getQueryBuilder(): QueryBuilder
	{
		if ($this->queryBuilder === NULL) {
			$this->queryBuilder = new QueryBuilder();
		}

		return $this->queryBuilder;
	}


	public function createQuery(): QueryExecute
	{
		return new QueryExecute($this->getQueryBuilder(), $this);
	}

}
