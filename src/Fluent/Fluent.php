<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Fluent implements Sql
{
	public const QUERY_SELECT = 'select';
	public const QUERY_INSERT = 'insert';
	public const QUERY_UPDATE = 'update';
	public const QUERY_DELETE = 'delete';
	public const QUERY_TRUNCATE = 'truncate';

	public const PARAM_SELECT = 'select';
	public const PARAM_DISTINCT = 'distinct';
	public const PARAM_TABLES = 'tables';
	public const PARAM_TABLE_TYPES = 'table-types';
	public const PARAM_JOIN_CONDITIONS = 'join-conditions';
	public const PARAM_WHERE = 'where';
	public const PARAM_GROUPBY = 'groupBy';
	public const PARAM_HAVING = 'having';
	public const PARAM_ORDERBY = 'orderBy';
	public const PARAM_LIMIT = 'limit';
	public const PARAM_OFFSET = 'offset';
	public const PARAM_COMBINE_QUERIES = 'combine-queries';
	public const PARAM_INSERT_COLUMNS = 'insert-columns';
	public const PARAM_RETURNING = 'returning';
	public const PARAM_DATA = 'data';
	public const PARAM_ROWS = 'rows';
	public const PARAM_PREFIX = 'prefix';
	public const PARAM_SUFFIX = 'suffix';

	public const TABLE_TYPE_MAIN = 'main';
	public const TABLE_TYPE_FROM = 'from';
	public const TABLE_TYPE_JOINS = 'joins';

	private const JOIN_INNER = 'INNER JOIN';
	private const JOIN_LEFT_OUTER = 'LEFT OUTER JOIN';
	private const JOIN_RIGHT_OUTER = 'RIGHT OUTER JOIN';
	private const JOIN_FULL_OUTER = 'FULL OUTER JOIN';
	public const JOIN_CROSS = 'CROSS JOIN';

	private const COMBINE_UNION = 'UNION';
	private const COMBINE_UNION_ALL = 'UNION ALL';
	private const COMBINE_INTERSECT = 'INTERSECT';
	private const COMBINE_EXCEPT = 'EXCEPT';

	private const DEFAULT_PARAMS = [
		self::PARAM_SELECT => [],
		self::PARAM_DISTINCT => FALSE,
		self::PARAM_TABLES => [],
		self::PARAM_TABLE_TYPES => [
			self::TABLE_TYPE_MAIN => NULL,
			self::TABLE_TYPE_FROM => [],
			self::TABLE_TYPE_JOINS => [],
		],
		self::PARAM_JOIN_CONDITIONS => [],
		self::PARAM_WHERE => [],
		self::PARAM_GROUPBY => [],
		self::PARAM_HAVING => [],
		self::PARAM_ORDERBY => [],
		self::PARAM_LIMIT => NULL,
		self::PARAM_OFFSET => NULL,
		self::PARAM_COMBINE_QUERIES => [],
		self::PARAM_INSERT_COLUMNS => [],
		self::PARAM_RETURNING => [],
		self::PARAM_DATA => [],
		self::PARAM_ROWS => [],
		self::PARAM_PREFIX => [],
		self::PARAM_SUFFIX => [],
	];

	/** @var string */
	private $queryType = self::QUERY_SELECT;

	/** @var array */
	private $params = self::DEFAULT_PARAMS;

	/** @var Db\Query|NULL */
	private $query;


	/**
	 * @param string|Sql|Db\Queryable $table
	 * @param string|NULL $alias
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function table($table, ?string $alias = NULL): Sql
	{
		return $this->addTable(self::TABLE_TYPE_MAIN, $table, $alias);
	}


	/**
	 * @param array $columns
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): Sql
	{
		$this->updateFluent();

		\array_walk($columns, function ($column, $alias): void {
			if (\is_int($alias)) {
				$alias = NULL;
			}
			$this->checkQueryable($column, $alias);
		});

		$this->params[self::PARAM_SELECT] = \array_merge($this->params[self::PARAM_SELECT], $columns);

		return $this;
	}


	/**
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_DISTINCT] = TRUE;
		return $this;
	}


	/**
	 * @param string|Sql|Db\Queryable $from
	 * @param string|NULL $alias
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): Sql
	{
		return $this->addTable(self::TABLE_TYPE_FROM, $from, $alias);
	}



	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->addTable(self::JOIN_INNER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->addTable(self::JOIN_LEFT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->addTable(self::JOIN_RIGHT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): Sql
	{
		return $this->addTable(self::JOIN_FULL_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|Sql|Db\Queryable $join table or query
	 * @param string|NULL $alias
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): Sql
	{
		return $this->addTable(self::JOIN_CROSS, $join, $alias);
	}


	/**
	 * @param string $type
	 * @param string|Sql|Db\Queryable $name
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	private function addTable(string $type, $name, ?string $alias, $onCondition = NULL): self
	{
		$this->updateFluent();

		$this->checkQueryable($name, $alias);

		if (($type === self::TABLE_TYPE_MAIN) && ($this->params[self::PARAM_TABLE_TYPES][self::TABLE_TYPE_MAIN] !== NULL)) {
			throw Exceptions\FluentException::onlyOneMainTable();
		}

		if ($alias === NULL) {
			$alias = $name;
		}

		if (isset($this->params[self::PARAM_TABLES][$alias])) {
			throw Exceptions\FluentException::tableAliasAlreadyExists($alias);
		}

		$this->params[self::PARAM_TABLES][$alias] = [$name, $type];

		if ($type === self::TABLE_TYPE_MAIN) {
			$this->params[self::PARAM_TABLE_TYPES][$type] = $alias;
		} else {
			$this->params[self::PARAM_TABLE_TYPES][$type === self::TABLE_TYPE_FROM ? $type : self::TABLE_TYPE_JOINS][] = $alias;
		}

		if ($onCondition !== NULL) {
			$this->params[self::PARAM_JOIN_CONDITIONS][$alias] = \array_merge(
				$this->params[self::PARAM_JOIN_CONDITIONS][$alias] ?? [],
				$this->normalizeOn($onCondition)
			);
		}

		return $this;
	}


	/**
	 * @param string $alias
	 * @param string|array|Complex $condition
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, $condition): Sql
	{
		$this->updateFluent();

		$this->params[self::PARAM_JOIN_CONDITIONS][$alias] = \array_merge(
			$this->params[self::PARAM_JOIN_CONDITIONS][$alias] ?? [],
			$this->normalizeOn($condition)
		);

		return $this;
	}


	/**
	 * @param string|array|Complex $condition
	 * @return array
	 */
	private function normalizeOn($condition): array
	{
		if ($condition instanceof Complex) {
			return [$condition];
		}

		if (!\is_array($condition)) {
			return [[$condition]];
		}

		$first = \reset($condition);

		if (\is_array($first)) {
			return $condition;
		}

		return [$condition];
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function where($condition, ...$params): Sql
	{
		$this->updateFluent();
		\array_unshift($params, $condition);
		$this->params[self::PARAM_WHERE][] = $params;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function whereAnd(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_WHERE][] = Complex::createAnd($conditions, NULL, $this);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_WHERE][] = Complex::createOr($conditions, NULL, $this);
	}


	/**
	 * @param array $columns
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_GROUPBY] = \array_merge($this->params[self::PARAM_GROUPBY], $columns);
		return $this;
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function having($condition, ...$params): Sql
	{
		$this->updateFluent();
		\array_unshift($params, $condition);
		$this->params[self::PARAM_HAVING][] = $params;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function havingAnd(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_HAVING][] = Complex::createAnd($conditions, NULL, $this);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_HAVING][] = Complex::createOr($conditions, NULL, $this);
	}


	/**
	 * @param array $columns
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $columns): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_ORDERBY] = \array_merge($this->params[self::PARAM_ORDERBY], $columns);
		return $this;
	}


	/**
	 * @param int $limit
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_LIMIT] = $limit;
		return $this;
	}


	/**
	 * @param int $offset
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_OFFSET] = $offset;
		return $this;
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return static
	 */
	public function union($query): Sql
	{
		return $this->addCombine(self::COMBINE_UNION, $query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return static
	 */
	public function unionAll($query): Sql
	{
		return $this->addCombine(self::COMBINE_UNION_ALL, $query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return static
	 */
	public function intersect($query): Sql
	{
		return $this->addCombine(self::COMBINE_INTERSECT, $query);
	}


	/**
	 * @param string|Sql|Db\Queryable $query
	 * @return static
	 */
	public function except($query): Sql
	{
		return $this->addCombine(self::COMBINE_EXCEPT, $query);
	}


	/**
	 * @param string $type
	 * @param string|Sql|Db\Queryable $query
	 * @return static
	 */
	private function addCombine(string $type, $query): self
	{
		$this->params[self::PARAM_COMBINE_QUERIES][] = [$query, $type];
		return $this;
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): Sql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_INSERT;

		if ($into !== NULL) {
			$this->table($into);
		}

		$this->params[self::PARAM_INSERT_COLUMNS] = $columns;

		return $this;
	}


	/**
	 * @param array $data
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): Sql
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];
		return $this;
	}


	/**
	 * @param array $rows
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): Sql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_ROWS] = \array_merge($this->params[self::PARAM_ROWS], $rows);

		return $this;
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): Sql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_UPDATE;

		if ($table !== NULL) {
			$this->table($table, $alias);
		}

		return $this;
	}


	/**
	 * @param array $data
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): Sql
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_UPDATE;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];
		return $this;
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): Sql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_DELETE;

		if ($from !== NULL) {
			$this->table($from, $alias);
		}

		return $this;
	}


	/**
	 * @param array $returning
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): Sql
	{
		$this->updateFluent();
		$this->params[self::PARAM_RETURNING] = \array_merge($this->params[self::PARAM_RETURNING], $returning);
		return $this;
	}


	/**
	 * @param string|NULL $table
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): Sql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_TRUNCATE;

		if ($table !== NULL) {
			$this->table($table);
		}

		return $this;
	}


	/**
	 * @param string $queryPrefix
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function prefix(string $queryPrefix, ...$params): Sql
	{
		$this->updateFluent();
		\array_unshift($params, $queryPrefix);
		$this->params[self::PARAM_PREFIX][] = $params;
		return $this;
	}


	/**
	 * @param string $querySufix
	 * @param mixed ...$params
	 * @return static
	 * @throws Exceptions\FluentException
	 */
	public function sufix(string $querySufix, ...$params): Sql
	{
		$this->updateFluent();
		\array_unshift($params, $querySufix);
		$this->params[self::PARAM_SUFFIX][] = $params;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function reset(string $param): self
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\FluentException::nonExistingParamToReset($param, \array_keys(self::DEFAULT_PARAMS));
		}

		$this->updateFluent();

		$this->params[$param] = self::DEFAULT_PARAMS[$param];

		return $this;
	}


	protected function updateFluent(): void
	{
		$this->query = NULL;
	}


	/**
	 * @param self|Db\Queryable|mixed $data
	 * @param string|NULL $alias
	 * @throws Exceptions\FluentException
	 */
	private function checkQueryable($data, ?string $alias): void
	{
		if ((($data instanceof self) || ($data instanceof Db\Queryable)) && ($alias === NULL)) {
			throw Exceptions\FluentException::queryableMustHaveAlias();
		} else if (!\is_scalar($data) && !($data instanceof self) && !($data instanceof Db\Queryable)) {
			throw Exceptions\FluentException::columnMustBeScalarOrQueryable();
		}
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getQuery(): Db\Query
	{
		if ($this->query === NULL) {
			$this->query = $this->createQueryBuilder()->createQuery();
		}
		return $this->query;
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function prepareSql(): Db\Query
	{
		return Db\Helper::prepareSql($this->getQuery());
	}


	protected function createQueryBuilder(): QueryBuilder
	{
		return new QueryBuilder($this->queryType, $this->params);
	}

}
