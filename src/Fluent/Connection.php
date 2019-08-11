<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements Sql
{

	/**
	 * @param string|Sql|Db\Queryable $table
	 * @param string|NULL $alias
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function table($table, ?string $alias = NULL): Sql
	{
		return $this->fluent()->table($table, $alias);
	}


	/**
	 * @param array $columns
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): Sql
	{
		return $this->fluent()->select($columns);
	}


	/**
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): Sql
	{
		return $this->fluent()->distinct();
	}


	/**
	 * @param string|Sql|Db\Queryable $from
	 * @param string|NULL $alias
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): Sql
	{
		return $this->fluent()->from($from, $alias);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->join($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fluent()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): Sql
	{
		return $this->fluent()->crossJoin($join, $alias);
	}


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, $condition): Sql
	{
		return $this->fluent()->on($alias, $condition);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function where($condition, ...$params): Sql
	{
		return $this->fluent()->where($condition, ...$params);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		return $this->fluent()->whereAnd($conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		return $this->fluent()->whereOr($conditions);
	}


	/**
	 * @param array $columns
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): Sql
	{
		return $this->fluent()->groupBy($columns);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function having($condition, ...$params): Sql
	{
		return $this->fluent()->having($condition, ...$params);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		return $this->fluent()->havingAnd($conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		return $this->fluent()->havingOr($conditions);
	}


	/**
	 * @param array $colums
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $colums): Sql
	{
		return $this->fluent()->orderBy($colums);
	}


	/**
	 * @param int $limit
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): Sql
	{
		return $this->fluent()->limit($limit);
	}


	/**
	 * @param int $offset
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): Sql
	{
		return $this->fluent()->offset($offset);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return FluentExecute
	 */
	public function union($query): Sql
	{
		return $this->fluent()->union($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return FluentExecute
	 */
	public function unionAll($query): Sql
	{
		return $this->fluent()->unionAll($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return FluentExecute
	 */
	public function intersect($query): Sql
	{
		return $this->fluent()->intersect($query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return FluentExecute
	 */
	public function except($query): Sql
	{
		return $this->fluent()->except($query);
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Sql
	{
		return $this->fluent()->insert($into, $columns);
	}


	/**
	 * @param array $data
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): Sql
	{
		return $this->fluent()->values($data);
	}


	/**
	 * @param array $rows
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): Sql
	{
		return $this->fluent()->rows($rows);
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Sql
	{
		return $this->fluent()->update($table, $alias);
	}


	/**
	 * @param array $data
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): Sql
	{
		return $this->fluent()->set($data);
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Sql
	{
		return $this->fluent()->delete($from, $alias);
	}


	/**
	 * @param array $returning
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): Sql
	{
		return $this->fluent()->returning($returning);
	}


	/**
	 * @param string|NULL $table
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): Sql
	{
		return $this->fluent()->truncate($table);
	}


	/**
	 * @param string $queryPrefix
	 * @param mixed ...$params
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function prefix(string $queryPrefix, ...$params): Sql
	{
		return $this->fluent()->prefix($queryPrefix, ...$params);
	}


	/**
	 * @param string $querySufix
	 * @param mixed ...$params
	 * @return FluentExecute
	 * @throws Exceptions\FluentException
	 */
	public function sufix(string $querySufix, ...$params): Sql
	{
		return $this->fluent()->sufix($querySufix, ...$params);
	}


	public function fluent(): FluentExecute
	{
		return new FluentExecute($this);
	}

}
