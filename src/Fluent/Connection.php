<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements FluentSql
{

	/**
	 * @param string|Fluent|Db\Query $from
	 * @param string|NULL $alias
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function table($from, ?string $alias = NULL): FluentSql
	{
		return $this->fluent()->table($from, $alias);
	}


	/**
	 * @param array $columns
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): FluentSql
	{
		return $this->fluent()->select($columns);
	}


	/**
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): FluentSql
	{
		return $this->fluent()->distinct();
	}


	/**
	 * @param string|Fluent|Db\Query $from
	 * @param string|NULL $alias
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): FluentSql
	{
		return $this->fluent()->from($from, $alias);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->join($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fluent()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Fluent|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): FluentSql
	{
		return $this->fluent()->crossJoin($join, $alias);
	}


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, $condition): FluentSql
	{
		return $this->fluent()->on($alias, $condition);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function where($condition, ...$params): FluentSql
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
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): FluentSql
	{
		return $this->fluent()->groupBy($columns);
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function having($condition, ...$params): FluentSql
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
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $colums): FluentSql
	{
		return $this->fluent()->orderBy($colums);
	}


	/**
	 * @param int $limit
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): FluentSql
	{
		return $this->fluent()->limit($limit);
	}


	/**
	 * @param int $offset
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): FluentSql
	{
		return $this->fluent()->offset($offset);
	}


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	public function union($query): FluentSql
	{
		return $this->fluent()->union($query);
	}


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	public function unionAll($query): FluentSql
	{
		return $this->fluent()->unionAll($query);
	}


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	public function intersect($query): FluentSql
	{
		return $this->fluent()->intersect($query);
	}


	/**
	 * @param string|Fluent|Db\Query $query
	 * @return Fluent
	 */
	public function except($query): FluentSql
	{
		return $this->fluent()->except($query);
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): FluentSql
	{
		return $this->fluent()->insert($into, $columns);
	}


	/**
	 * @param array $data
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): FluentSql
	{
		return $this->fluent()->values($data);
	}


	/**
	 * @param array $rows
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): FluentSql
	{
		return $this->fluent()->rows($rows);
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): FluentSql
	{
		return $this->fluent()->update($table, $alias);
	}


	/**
	 * @param array $data
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): FluentSql
	{
		return $this->fluent()->set($data);
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): FluentSql
	{
		return $this->fluent()->delete($from, $alias);
	}


	/**
	 * @param array $returning
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): FluentSql
	{
		return $this->fluent()->returning($returning);
	}


	/**
	 * @param string|NULL $table
	 * @return Fluent
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): FluentSql
	{
		return $this->fluent()->truncate($table);
	}


	protected function fluent(): Fluent
	{
		return new Fluent($this);
	}

}
