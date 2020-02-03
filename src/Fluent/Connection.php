<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements Fluent
{
	/** @var QueryBuilder */
	private $queryBuilder;


	/**
	 * @param string|Fluent|Db\Sql $table
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function table($table, ?string $alias = NULL): Fluent
	{
		return $this->createQuery()->table($table, $alias);
	}


	/**
	 * @param array $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): Fluent
	{
		return $this->createQuery()->select($columns);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): Fluent
	{
		return $this->createQuery()->distinct();
	}


	/**
	 * @param string|Fluent|Db\Sql $from
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function from($from, ?string $alias = NULL): Fluent
	{
		return $this->createQuery()->from($from, $alias);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->join($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->createQuery()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Sql $join table or query
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin($join, ?string $alias = NULL): Fluent
	{
		return $this->createQuery()->crossJoin($join, $alias);
	}


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, $condition): Fluent
	{
		return $this->createQuery()->on($alias, $condition);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function where($condition, ...$params): Fluent
	{
		return $this->createQuery()->where($condition, ...$params);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->whereAnd($conditions);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		return $this->createQuery()->whereOr($conditions);
	}


	/**
	 * @param array $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function groupBy(array $columns): Fluent
	{
		return $this->createQuery()->groupBy($columns);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function having($condition, ...$params): Fluent
	{
		return $this->createQuery()->having($condition, ...$params);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		return $this->createQuery()->havingAnd($conditions);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		return $this->createQuery()->havingOr($conditions);
	}


	/**
	 * @param array $colums
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function orderBy(array $colums): Fluent
	{
		return $this->createQuery()->orderBy($colums);
	}


	/**
	 * @param int $limit
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): Fluent
	{
		return $this->createQuery()->limit($limit);
	}


	/**
	 * @param int $offset
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): Fluent
	{
		return $this->createQuery()->offset($offset);
	}


	/**
	 * @param string|Fluent|Db\Sql $query
	 * @return QueryExecute
	 */
	public function union($query): Fluent
	{
		return $this->createQuery()->union($query);
	}


	/**
	 * @param string|Fluent|Db\Sql $query
	 * @return QueryExecute
	 */
	public function unionAll($query): Fluent
	{
		return $this->createQuery()->unionAll($query);
	}


	/**
	 * @param string|Fluent|Db\Sql $query
	 * @return QueryExecute
	 */
	public function intersect($query): Fluent
	{
		return $this->createQuery()->intersect($query);
	}


	/**
	 * @param string|Fluent|Db\Sql $query
	 * @return QueryExecute
	 */
	public function except($query): Fluent
	{
		return $this->createQuery()->except($query);
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Fluent
	{
		return $this->createQuery()->insert($into, $columns);
	}


	/**
	 * @param array $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): Fluent
	{
		return $this->createQuery()->values($data);
	}


	/**
	 * @param array $rows
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): Fluent
	{
		return $this->createQuery()->rows($rows);
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Fluent
	{
		return $this->createQuery()->update($table, $alias);
	}


	/**
	 * @param array $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): Fluent
	{
		return $this->createQuery()->set($data);
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Fluent
	{
		return $this->createQuery()->delete($from, $alias);
	}


	/**
	 * @param array $returning
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): Fluent
	{
		return $this->createQuery()->returning($returning);
	}


	/**
	 * @param string|NULL $table
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function truncate(?string $table = NULL): Fluent
	{
		return $this->createQuery()->truncate($table);
	}


	/**
	 * @param string $queryPrefix
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, ...$params): Fluent
	{
		return $this->createQuery()->prefix($queryPrefix, ...$params);
	}


	/**
	 * @param string $querySufix
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function sufix(string $querySufix, ...$params): Fluent
	{
		return $this->createQuery()->sufix($querySufix, ...$params);
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
