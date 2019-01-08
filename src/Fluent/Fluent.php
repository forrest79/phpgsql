<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Fluent implements FluentSql, \Countable, \IteratorAggregate
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
	];

	/** @var Db\Connection|NULL */
	private $connection;

	/** @var string */
	private $queryType = self::QUERY_SELECT;

	/** @var array */
	private $params = self::DEFAULT_PARAMS;

	/** @var Db\Result|NULL */
	private $result;

	/** @var Db\Query|NULL */
	private $query;


	public function __construct(?Db\Connection $connection = NULL)
	{
		$this->connection = $connection;
	}


	/**
	 * @param string|self|Db\Query $table
	 * @param string|NULL $alias
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function table($table, ?string $alias = NULL): FluentSql
	{
		return $this->addTable(self::TABLE_TYPE_MAIN, $table, $alias);
	}


	/**
	 * @param array $columns
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): FluentSql
	{
		$this->updateFluent();

		\array_walk($columns, function($column, $alias): void {
			if (is_int($alias)) {
				$alias = NULL;
			}
			$this->checkQueryable($column, $alias);
		});

		$this->params[self::PARAM_SELECT] = array_merge($this->params[self::PARAM_SELECT], $columns);

		return $this;
	}


	/**
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_DISTINCT] = TRUE;
		return $this;
	}


	/**
	 * @param string|self|Db\Query $from
	 * @param string|NULL $alias
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): FluentSql
	{
		return $this->addTable(self::TABLE_TYPE_FROM, $from, $alias);
	}



	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->addTable(self::JOIN_INNER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->addTable(self::JOIN_LEFT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->addTable(self::JOIN_RIGHT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL): FluentSql
	{
		return $this->addTable(self::JOIN_FULL_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @param string|self|Db\Query $join table or query
	 * @param string|NULL $alias
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): FluentSql
	{
		return $this->addTable(self::JOIN_CROSS, $join, $alias);
	}


	/**
	 * @param string $type
	 * @param string|self|Db\Query $name
	 * @param string|NULL $alias
	 * @param string|array|Complex|NULL $onCondition
	 * @return self
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, $condition): FluentSql
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

		if (!is_array($condition)) {
			return [[$condition]];
		}

		$first = reset($condition);

		if (is_array($first)) {
			return $condition;
		}

		return [$condition];
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function where($condition, ...$params): FluentSql
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_GROUPBY] = array_merge($this->params[self::PARAM_GROUPBY], $columns);
		return $this;
	}


	/**
	 * @param string|Complex $condition
	 * @param mixed ...$params
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function having($condition, ...$params): FluentSql
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $columns): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_ORDERBY] = array_merge($this->params[self::PARAM_ORDERBY], $columns);
		return $this;
	}


	/**
	 * @param int $limit
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_LIMIT] = $limit;
		return $this;
	}


	/**
	 * @param int $offset
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_OFFSET] = $offset;
		return $this;
	}


	/**
	 * @param string|self|Db\Query $query
	 * @return self
	 */
	public function union($query): FluentSql
	{
		return $this->addCombine(self::COMBINE_UNION, $query);
	}


	/**
	 * @param string|self|Db\Query $query
	 * @return self
	 */
	public function unionAll($query): FluentSql
	{
		return $this->addCombine(self::COMBINE_UNION_ALL, $query);
	}


	/**
	 * @param string|self|Db\Query $query
	 * @return self
	 */
	public function intersect($query): FluentSql
	{
		return $this->addCombine(self::COMBINE_INTERSECT, $query);
	}


	/**
	 * @param string|self|Db\Query $query
	 * @return self
	 */
	public function except($query): FluentSql
	{
		return $this->addCombine(self::COMBINE_EXCEPT, $query);
	}


	/**
	 * @param string $type
	 * @param string|self|Db\Query $query
	 * @return self
	 */
	private function addCombine(string $type, $query): self
	{
		$this->params[self::PARAM_COMBINE_QUERIES][] = [$query, $type];
		return $this;
	}


	/**
	 * @param string|NULL $into
	 * @param array|NULL $columns
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): FluentSql
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): FluentSql
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_DATA] = $data;
		return $this;
	}


	/**
	 * @param array $rows
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): FluentSql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_ROWS] = $rows;

		return $this;
	}


	/**
	 * @param string|NULL $table
	 * @param string|NULL $alias
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): FluentSql
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): FluentSql
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_UPDATE;
		$this->params[self::PARAM_DATA] = $data;
		return $this;
	}


	/**
	 * @param string|NULL $from
	 * @param string|NULL $alias
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): FluentSql
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
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): FluentSql
	{
		$this->updateFluent();
		$this->params[self::PARAM_RETURNING] = \array_merge($this->params[self::PARAM_RETURNING], $returning);
		return $this;
	}


	/**
	 * @param string|NULL $table
	 * @return self
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): FluentSql
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_TRUNCATE;

		if ($table !== NULL) {
			$this->table($table);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function reset(string $param): self
	{
		if (!array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\FluentException::nonExistingParamToReset($param, array_keys(self::DEFAULT_PARAMS));
		}

		$this->updateFluent();

		$this->params[$param] = self::DEFAULT_PARAMS[$param];

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	private function updateFluent(): void
	{
		if ($this->result !== NULL) {
			throw Exceptions\FluentException::cantUpdateFluentAfterExecute();
		}
		$this->query = NULL;
	}


	/**
	 * @param self|Db\Query|mixed $data
	 * @param string|NULL $alias
	 * @throws Exceptions\FluentException
	 */
	private function checkQueryable($data, ?string $alias): void
	{
		if ((($data instanceof self) || ($data instanceof Db\Query)) && ($alias === NULL)) {
			throw Exceptions\FluentException::queryableMustHaveAlias();
		} else if (!is_scalar($data) && !($data instanceof self) && !($data instanceof Db\Query)) {
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


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function execute(): Db\Result
	{
		if ($this->result === NULL) {
			$this->result = $this->getConnection()->query($this->getQuery());
		}
		return $this->result;
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function reexecute(): Db\Result
	{
		if ($this->result !== NULL) {
			$this->free();
		}
		$this->result = NULL;
		return $this->execute();
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function free(): bool
	{
		if ($this->result === NULL) {
			throw Exceptions\FluentException::youMustExecuteFluentBeforeThat();
		}
		return $this->result->free();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function count(): int
	{
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getIterator(): Db\ResultIterator
	{
		return $this->execute()->getIterator();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getAffectedRows(): int
	{
		return $this->execute()->getAffectedRows();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetch(): ?Db\Row
	{
		return $this->execute()->fetch();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 * @return mixed value on success, NULL if no next record
	 */
	public function fetchSingle()
	{
		return $this->execute()->fetchSingle();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 * @return Db\Row[]
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		return $this->execute()->fetchAll($offset, $limit);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->execute()->fetchAssoc($assoc);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		return $this->execute()->fetchPairs($key, $value);
	}


	protected function createQueryBuilder(): QueryBuilder
	{
		return new QueryBuilder($this->queryType, $this->params);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	private function getConnection(): Db\Connection
	{
		if ($this->connection === NULL) {
			throw Exceptions\FluentException::youNeedConnectionForThisAction();
		}
		return $this->connection;
	}

}
