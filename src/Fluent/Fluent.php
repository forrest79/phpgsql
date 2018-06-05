<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Fluent implements Sql, \Countable, \IteratorAggregate
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
	private const JOIN_CROSS = 'CROSS JOIN';

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

	/** @var Db\Connection */
	private $connection;

	/** @var string */
	private $queryType = self::QUERY_SELECT;

	/** @var array */
	private $params = self::DEFAULT_PARAMS;

	/** @var Db\Result */
	private $result;

	/** @var Db\Query */
	private $query;


	private function __construct(?Db\Connection $connection = NULL)
	{
		$this->connection = $connection;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function table($table, ?string $alias = NULL): self
	{
		return $this->addTable(self::TABLE_TYPE_MAIN, $table, $alias);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function select(array $columns): self
	{
		$this->updateFluent();

		\array_walk($columns, function($column, $alias): void {
			$this->checkQueryable($column, $alias);
		});

		$this->params[self::PARAM_SELECT] = array_merge($this->params[self::PARAM_SELECT], $columns);

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function distinct(): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_DISTINCT] = TRUE;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function from($from, ?string $alias = NULL): self
	{
		return $this->addTable(self::TABLE_TYPE_FROM, $from, $alias);
	}



	/**
	 * @throws Exceptions\FluentException
	 */
	public function join($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->innerJoin($join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function innerJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->addTable(self::JOIN_INNER, $join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function leftJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->leftOuterJoin($join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function leftOuterJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->addTable(self::JOIN_LEFT_OUTER, $join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function rightJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->rightOuterJoin($join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function rightOuterJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->addTable(self::JOIN_RIGHT_OUTER, $join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function fullJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->fullOuterJoin($join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function fullOuterJoin($join, ?string $alias = NULL, array $onConditions = []): self
	{
		return $this->addTable(self::JOIN_FULL_OUTER, $join, $alias, $onConditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function crossJoin($join, ?string $alias = NULL): self
	{
		return $this->addTable(self::JOIN_CROSS, $join, $alias);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	private function addTable(string $type, $name, $alias, array $onConditions = []): self
	{
		$this->updateFluent();

		$this->checkQueryable($name, $alias);

		if (($type === self::TABLE_TYPE_MAIN) && ($this->params[self::PARAM_TABLE_TYPES][self::TABLE_TYPE_MAIN] !== NULL)) {
			throw Exceptions\FluentException::onlyOneMainTable();
		}

		if (!$alias) {
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

		if ($onConditions) {
			$this->params[self::PARAM_JOIN_CONDITIONS][$alias] = \array_merge(
				$this->params[self::PARAM_JOIN_CONDITIONS][$alias] ?? [],
				$onConditions
			);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function on(string $alias, array $conditions): self
	{
		$this->updateFluent();

		$this->params[self::PARAM_JOIN_CONDITIONS][$alias] = \array_merge(
			$this->params[self::PARAM_JOIN_CONDITIONS][$alias] ?? [],
			$conditions
		);

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function where(string $condition, ...$params): self
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
		return $this->params[self::PARAM_WHERE][] = Complex::createAnd($this, $conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function whereOr(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_WHERE][] = Complex::createOr($this, $conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function groupBy(array $columns): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_GROUPBY] = array_merge($this->params[self::PARAM_GROUPBY], $columns);
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function having(string $condition, ...$params): self
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
		return $this->params[self::PARAM_HAVING][] = Complex::createAnd($this, $conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function havingOr(array $conditions = []): Complex
	{
		$this->updateFluent();
		return $this->params[self::PARAM_HAVING][] = Complex::createOr($this, $conditions);
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function orderBy(array $columns): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_ORDERBY] = array_merge($this->params[self::PARAM_ORDERBY], $columns);
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function limit(int $limit): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_LIMIT] = $limit;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function offset(int $offset): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_OFFSET] = $offset;
		return $this;
	}


	public function union($query): self
	{
		return $this->addCombine(self::COMBINE_UNION, $query);
	}


	public function unionAll($query): self
	{
		return $this->addCombine(self::COMBINE_UNION_ALL, $query);
	}


	public function intersect($query): self
	{
		return $this->addCombine(self::COMBINE_INTERSECT, $query);
	}


	public function except($query): self
	{
		return $this->addCombine(self::COMBINE_EXCEPT, $query);
	}


	private function addCombine(string $type, $query): self
	{
		$this->params[self::PARAM_COMBINE_QUERIES][] = [$query, $type];
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function insert(?string $into = NULL, ?array $columns = []): self
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
	 * @throws Exceptions\FluentException
	 */
	public function values(array $data): self
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_DATA] = $data;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function rows(array $rows): self
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_ROWS] = $rows;

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function update(?string $table = NULL, ?string $alias = NULL): self
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_UPDATE;

		if ($table !== NULL) {
			$this->table($table, $alias);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function set(array $data): self
	{
		$this->updateFluent();
		$this->queryType = self::QUERY_UPDATE;
		$this->params[self::PARAM_DATA] = $data;
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function delete(?string $from = NULL, ?string $alias = NULL): self
	{
		$this->updateFluent();

		$this->queryType = self::QUERY_DELETE;

		if ($from !== NULL) {
			$this->table($from, $alias);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function returning(array $returning): self
	{
		$this->updateFluent();
		$this->params[self::PARAM_RETURNING] = \array_merge($this->params[self::PARAM_RETURNING], $returning);
		return $this;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	public function truncate(?string $table = NULL): self
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
	 * @throws Exceptions\FluentException
	 */
	private function checkQueryable($data, $alias): void
	{
		if (($alias !== NULL) && !is_scalar($alias)) {
			throw Exceptions\FluentException::aliasMustBeScalar();
		} else if ((($data instanceof self) || ($data instanceof Db\Query)) && !$alias) {
			throw Exceptions\FluentException::queryableMustHaveAlias();
		} else if (!is_scalar($data)) {
			throw Exceptions\FluentException::columnMustBeScalarOrQueryable();
		}
	}


	public function getQuery(): Db\Query
	{
		if ($this->query === NULL) {
			$this->query = QueryBuilder::create($this->queryType, $this->params)->createQuery();
		}
		return $this->query;
	}


	public function prepareSql(): Db\Query
	{
		return Db\Helper::prepareSql($this->getQuery());
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
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
	 */
	public function reexecute(): Db\Result
	{
		$this->result = NULL;
		return $this->execute();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function count(): int
	{
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function getIterator(): Db\ResultIterator
	{
		return $this->execute()->getIterator();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function fetch(): ?Db\Row
	{
		return $this->execute()->fetch();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function fetchSingle()
	{
		return $this->execute()->fetchSingle();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		return $this->execute()->fetchAll($offset, $limit);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->execute()->fetchAssoc($assoc);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 */
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		return $this->execute()->fetchPairs($key, $value);
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


	public static function create(?Db\Connection $connection = NULL)
	{
		return new self($connection);
	}

}
