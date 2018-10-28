<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Connection extends Db\Connection implements FluentSql
{

	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function table($from, ?string $alias = NULL): Fluent
	{
		return $this->fluent()->table($from, $alias);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): Fluent
	{
		return $this->fluent()->select($columns);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): Fluent
	{
		return $this->fluent()->distinct();
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): Fluent
	{
		return $this->fluent()->from($from, $alias);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->join($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->leftJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->rightJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->fullJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Fluent
	{
		return $this->fluent()->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): Fluent
	{
		return $this->fluent()->crossJoin($join, $alias);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, $condition): Fluent
	{
		return $this->fluent()->on($alias, $condition);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function where($condition, ...$params): Fluent
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
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): Fluent
	{
		return $this->fluent()->groupBy($columns);
	}


	/**
	 * @inheritdoc
	 * @throws Exceptions\FluentException
	 */
	public function having($condition, ...$params): Fluent
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
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $colums): Fluent
	{
		return $this->fluent()->orderBy($colums);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): Fluent
	{
		return $this->fluent()->limit($limit);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): Fluent
	{
		return $this->fluent()->offset($offset);
	}


	public function union($query): Fluent
	{
		return $this->fluent()->union($query);
	}


	public function unionAll($query): Fluent
	{
		return $this->fluent()->unionAll($query);
	}


	public function intersect($query): Fluent
	{
		return $this->fluent()->intersect($query);
	}


	public function except($query): Fluent
	{
		return $this->fluent()->except($query);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Fluent
	{
		return $this->fluent()->insert($into, $columns);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): Fluent
	{
		return $this->fluent()->values($data);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): Fluent
	{
		return $this->fluent()->rows($rows);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Fluent
	{
		return $this->fluent()->update($table, $alias);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): Fluent
	{
		return $this->fluent()->set($data);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Fluent
	{
		return $this->fluent()->delete($from, $alias);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): Fluent
	{
		return $this->fluent()->returning($returning);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): Fluent
	{
		return $this->fluent()->truncate($table);
	}


	protected function fluent(): Fluent
	{
		return new Fluent($this);
	}

}
