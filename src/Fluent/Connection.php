<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements Sql
{
	/** @var QueryBuilder */
	private $queryBuilder;


	/**
	 * @param string|Sql|Db\Queryable $table
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function table($table, ?string $alias = NULL): Sql
	{
		return $this->createQuery()->table($table, $alias);
	}


	/**
	 * @param array $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): Sql
	{
		return $this->createQuery()->select($columns);
	}


	/**
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): Sql
	{
		return $this->createQuery()->distinct();
	}


	/**
	 * @param string|Sql|Db\Queryable $from
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function from($from, ?string $alias = NULL): Sql
	{
		return $this->createQuery()->from($from, $alias);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->join($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->createQuery()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin($join, ?string $alias = NULL): Sql
	{
		return $this->createQuery()->crossJoin($join, $alias);
	}


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, $condition): Sql
	{
		return $this->createQuery()->on($alias, $condition);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function where($condition, ...$params): Sql
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
	public function groupBy(array $columns): Sql
	{
		return $this->createQuery()->groupBy($columns);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function having($condition, ...$params): Sql
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
	public function orderBy(array $colums): Sql
	{
		return $this->createQuery()->orderBy($colums);
	}


	/**
	 * @param int $limit
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): Sql
	{
		return $this->createQuery()->limit($limit);
	}


	/**
	 * @param int $offset
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): Sql
	{
		return $this->createQuery()->offset($offset);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return QueryExecute
	 */
	public function union($query): Sql
	{
		return $this->createQuery()->union($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return QueryExecute
	 */
	public function unionAll($query): Sql
	{
		return $this->createQuery()->unionAll($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return QueryExecute
	 */
	public function intersect($query): Sql
	{
		return $this->createQuery()->intersect($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return QueryExecute
	 */
	public function except($query): Sql
	{
		return $this->createQuery()->except($query);
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Sql
	{
		return $this->createQuery()->insert($into, $columns);
	}


	/**
	 * @param array $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): Sql
	{
		return $this->createQuery()->values($data);
	}


	/**
	 * @param array $rows
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): Sql
	{
		return $this->createQuery()->rows($rows);
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Sql
	{
		return $this->createQuery()->update($table, $alias);
	}


	/**
	 * @param array $data
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): Sql
	{
		return $this->createQuery()->set($data);
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Sql
	{
		return $this->createQuery()->delete($from, $alias);
	}


	/**
	 * @param array $returning
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): Sql
	{
		return $this->createQuery()->returning($returning);
	}


	/**
	 * @param string|NULL $table
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function truncate(?string $table = NULL): Sql
	{
		return $this->createQuery()->truncate($table);
	}


	/**
	 * @param string $queryPrefix
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, ...$params): Sql
	{
		return $this->createQuery()->prefix($queryPrefix, ...$params);
	}


	/**
	 * @param string $querySufix
	 * @param mixed ...$params
	 * @return QueryExecute
	 * @throws Exceptions\QueryException
	 */
	public function sufix(string $querySufix, ...$params): Sql
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
