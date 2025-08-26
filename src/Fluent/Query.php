<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @phpstan-import-type QueryParams from QueryBuilder
 */
class Query implements Db\Sql
{
	public const string QUERY_SELECT = 'select';
	public const string QUERY_INSERT = 'insert';
	public const string QUERY_UPDATE = 'update';
	public const string QUERY_DELETE = 'delete';
	public const string QUERY_MERGE = 'merge';
	public const string QUERY_TRUNCATE = 'truncate';

	public const string PARAM_SELECT = 'select';
	public const string PARAM_DISTINCT = 'distinct';
	public const string PARAM_DISTINCTON = 'distinctOn';
	public const string PARAM_TABLES = 'tables';
	public const string PARAM_TABLE_TYPES = 'table-types';
	public const string PARAM_ON_CONDITIONS = 'on-conditions';
	public const string PARAM_LATERAL_TABLES = 'lateral-tables';
	public const string PARAM_WHERE = 'where';
	public const string PARAM_GROUPBY = 'groupBy';
	public const string PARAM_HAVING = 'having';
	public const string PARAM_ORDERBY = 'orderBy';
	public const string PARAM_LIMIT = 'limit';
	public const string PARAM_OFFSET = 'offset';
	public const string PARAM_COMBINE_QUERIES = 'combine-queries';
	public const string PARAM_INSERT_COLUMNS = 'insert-columns';
	public const string PARAM_INSERT_ONCONFLICT = 'insert-onconflict';
	public const string PARAM_RETURNING = 'returning';
	public const string PARAM_DATA = 'data';
	public const string PARAM_ROWS = 'rows';
	public const string PARAM_MERGE = 'merge';
	public const string PARAM_WITH = 'with';
	public const string PARAM_PREFIX = 'prefix';
	public const string PARAM_SUFFIX = 'suffix';

	public const string TABLE_TYPE_MAIN = 'main';
	public const string TABLE_TYPE_FROM = 'from';
	public const string TABLE_TYPE_JOINS = 'joins';
	public const string TABLE_TYPE_USING = 'using';

	private const string JOIN_INNER = 'INNER JOIN';
	private const string JOIN_LEFT_OUTER = 'LEFT OUTER JOIN';
	private const string JOIN_RIGHT_OUTER = 'RIGHT OUTER JOIN';
	private const string JOIN_FULL_OUTER = 'FULL OUTER JOIN';
	public const string JOIN_CROSS = 'CROSS JOIN';

	private const string COMBINE_UNION = 'UNION';
	private const string COMBINE_UNION_ALL = 'UNION ALL';
	private const string COMBINE_INTERSECT = 'INTERSECT';
	private const string COMBINE_EXCEPT = 'EXCEPT';

	public const string INSERT_ONCONFLICT_COLUMNS_OR_CONSTRAINT = 'columns-or-constraint';
	public const string INSERT_ONCONFLICT_WHERE = 'where';
	public const string INSERT_ONCONFLICT_DO = 'do';
	public const string INSERT_ONCONFLICT_DO_WHERE = 'do-where';

	public const string MERGE_WHEN_MATCHED = 'when-matched';
	public const string MERGE_WHEN_NOT_MATCHED = 'when-not-matched';

	public const string WITH_QUERIES = 'queries';
	public const string WITH_QUERIES_SUFFIX = 'queries-suffix';
	public const string WITH_QUERIES_NOT_MATERIALIZED = 'queries-not-materialized';
	public const string WITH_RECURSIVE = 'recursive';

	private const array DEFAULT_PARAMS = [
		self::PARAM_SELECT => [],
		self::PARAM_DISTINCT => false,
		self::PARAM_DISTINCTON => [],
		self::PARAM_TABLES => [],
		self::PARAM_TABLE_TYPES => [
			self::TABLE_TYPE_MAIN => null,
			self::TABLE_TYPE_FROM => [],
			self::TABLE_TYPE_JOINS => [],
			self::TABLE_TYPE_USING => null,
		],
		self::PARAM_ON_CONDITIONS => [],
		self::PARAM_LATERAL_TABLES => [],
		self::PARAM_WHERE => null,
		self::PARAM_GROUPBY => [],
		self::PARAM_HAVING => null,
		self::PARAM_ORDERBY => [],
		self::PARAM_LIMIT => null,
		self::PARAM_OFFSET => null,
		self::PARAM_COMBINE_QUERIES => [],
		self::PARAM_INSERT_COLUMNS => [],
		self::PARAM_INSERT_ONCONFLICT => [
			self::INSERT_ONCONFLICT_COLUMNS_OR_CONSTRAINT => null,
			self::INSERT_ONCONFLICT_WHERE => null,
			self::INSERT_ONCONFLICT_DO => null,
			self::INSERT_ONCONFLICT_DO_WHERE => null,
		],
		self::PARAM_RETURNING => [],
		self::PARAM_DATA => [],
		self::PARAM_ROWS => [],
		self::PARAM_MERGE => [],
		self::PARAM_WITH => [
			self::WITH_QUERIES => [],
			self::WITH_QUERIES_SUFFIX => [],
			self::WITH_QUERIES_NOT_MATERIALIZED => [],
			self::WITH_RECURSIVE => false,
		],
		self::PARAM_PREFIX => [],
		self::PARAM_SUFFIX => [],
	];

	private QueryBuilder $queryBuilder;

	private string $queryType = self::QUERY_SELECT;

	/** @phpstan-var QueryParams */
	private array $params = self::DEFAULT_PARAMS;

	private Db\SqlDefinition|null $sqlDefinition = null;

	private Db\Query|null $dbQuery = null;


	public function __construct(QueryBuilder $queryBuilder)
	{
		$this->queryBuilder = $queryBuilder;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function table(string|Db\Sql $table, string|null $alias = null): static
	{
		return $this->addTable(self::TABLE_TYPE_MAIN, $table, $alias);
	}


	/**
	 * @param array<int|string, string|int|bool|\BackedEnum|Db\Sql|null> $columns
	 * @throws Exceptions\QueryException
	 */
	public function select(array $columns): static
	{
		$this->resetQuery();

		foreach ($columns as $alias => &$column) {
			if (\is_int($alias)) {
				$alias = null;
			}

			if ($column === null) {
				$column = 'NULL';
			} else if ($column === true) {
				$column = 'TRUE';
			} else if ($column === false) {
				$column = 'FALSE';
			}

			$this->checkAlias($column, $alias);

			if (!($column instanceof Db\Sql) && !\is_scalar($column) && !($column instanceof \BackedEnum)) {
				throw Exceptions\QueryException::columnMustBeScalarOrEnumOrExpression();
			}
		}

		$this->params[self::PARAM_SELECT] = \array_merge($this->params[self::PARAM_SELECT], $columns);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function distinct(): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_DISTINCT] = true;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function distinctOn(string|Db\Sql ...$on): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_DISTINCTON] = \array_merge($this->params[self::PARAM_DISTINCTON], $on);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function from(string|Db\Sql $from, string|null $alias = null): static
	{
		return $this->addTable(self::TABLE_TYPE_FROM, $from, $alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function join(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->innerJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function innerJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->addTable(self::JOIN_INNER, $join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function leftJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->leftOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function leftOuterJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->addTable(self::JOIN_LEFT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function rightJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->rightOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function rightOuterJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->addTable(self::JOIN_RIGHT_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function fullJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->fullOuterJoin($join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function fullOuterJoin(
		string|Db\Sql $join,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->addTable(self::JOIN_FULL_OUTER, $join, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function crossJoin(string|Db\Sql $join, string|null $alias = null): static
	{
		return $this->addTable(self::JOIN_CROSS, $join, $alias);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	private function addTable(
		string $type,
		string|Db\Sql $name,
		string|null $alias,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		$this->resetQuery();

		$this->checkAlias($name, $alias);

		if (\in_array($type, [self::TABLE_TYPE_MAIN, self::TABLE_TYPE_USING], true) && ($this->params[self::PARAM_TABLE_TYPES][$type] !== null)) {
			throw ($type === self::TABLE_TYPE_MAIN) ? Exceptions\QueryException::onlyOneMainTable() : Exceptions\QueryException::mergeOnlyOneUsing();
		}

		if ($alias === null) {
			$alias = $name;
			\assert(\is_string($alias));
		}

		if (isset($this->params[self::PARAM_TABLES][$alias])) {
			throw Exceptions\QueryException::tableAliasAlreadyExists($alias);
		}

		$this->params[self::PARAM_TABLES][$alias] = [$name, $type];

		if (\in_array($type, [self::TABLE_TYPE_MAIN, self::TABLE_TYPE_USING], true)) {
			$this->params[self::PARAM_TABLE_TYPES][$type] = $alias;
		} else {
			$this->params[self::PARAM_TABLE_TYPES][$type === self::TABLE_TYPE_FROM ? $type : self::TABLE_TYPE_JOINS][] = $alias;
		}

		if ($onCondition !== null) {
			$this->getConditionParam(self::PARAM_ON_CONDITIONS, $alias)->add($onCondition);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function on(string $alias, string|Db\Sql $condition, mixed ...$params): static
	{
		$this->resetQuery();

		$this->getConditionParam(self::PARAM_ON_CONDITIONS, $alias)->add($condition, ...$params);

		return $this;
	}


	public function lateral(string $alias): static
	{
		$this->params[self::PARAM_LATERAL_TABLES][$alias] = $alias;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function where(string|Db\Sql $condition, mixed ...$params): static
	{
		$this->resetQuery();

		$this->getConditionParam(self::PARAM_WHERE)->add($condition, ...$params);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whereIf(bool $ifCondition, string|Db\Sql $condition, mixed ...$params): static
	{
		if ($ifCondition) {
			return $this->where($condition, ...$params);
		}

		return $this;
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereAnd(array $conditions = []): Condition
	{
		$this->resetQuery();

		$condition = Condition::createAnd($conditions, null, $this);
		$this->getConditionParam(self::PARAM_WHERE)->add($condition);

		return $condition;
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function whereOr(array $conditions = []): Condition
	{
		$this->resetQuery();

		$condition = Condition::createOr($conditions, null, $this);
		$this->getConditionParam(self::PARAM_WHERE)->add($condition);

		return $condition;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function groupBy(string ...$columns): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_GROUPBY] = \array_merge($this->params[self::PARAM_GROUPBY], $columns);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function having(string|Db\Sql $condition, mixed ...$params): static
	{
		$this->resetQuery();

		$this->getConditionParam(self::PARAM_HAVING)->add($condition, ...$params);

		return $this;
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingAnd(array $conditions = []): Condition
	{
		$this->resetQuery();

		$condition = Condition::createAnd($conditions, null, $this);
		$this->getConditionParam(self::PARAM_HAVING)->add($condition);

		return $condition;
	}


	/**
	 * @param list<string|list<mixed>|Db\Sql> $conditions
	 * @throws Exceptions\QueryException
	 */
	public function havingOr(array $conditions = []): Condition
	{
		$this->resetQuery();

		$condition = Condition::createOr($conditions, null, $this);
		$this->getConditionParam(self::PARAM_HAVING)->add($condition);

		return $condition;
	}


	private function getConditionParam(string $param, string|null $alias = null): Condition
	{
		if ($param === self::PARAM_ON_CONDITIONS) {
			if (!isset($this->params[$param][$alias])) {
				$this->params[$param][$alias] = Condition::createAnd();
			}

			return $this->params[$param][$alias];
		} else if (($param === self::PARAM_WHERE) || ($param === self::PARAM_HAVING)) {
			if ($this->params[$param] === null) {
				$this->params[$param] = Condition::createAnd();
			}

			return $this->params[$param];
		}

		throw new Exceptions\ShouldNotHappenException('Invalid argument: ' . $param);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function orderBy(string|Db\Sql ...$columns): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_ORDERBY] = \array_merge($this->params[self::PARAM_ORDERBY], $columns);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function limit(int $limit): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_LIMIT] = $limit;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function offset(int $offset): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_OFFSET] = $offset;

		return $this;
	}


	public function union(string|Db\Sql $query): static
	{
		return $this->addCombine(self::COMBINE_UNION, $query);
	}


	public function unionAll(string|Db\Sql $query): static
	{
		return $this->addCombine(self::COMBINE_UNION_ALL, $query);
	}


	public function intersect(string|Db\Sql $query): static
	{
		return $this->addCombine(self::COMBINE_INTERSECT, $query);
	}


	public function except(string|Db\Sql $query): static
	{
		return $this->addCombine(self::COMBINE_EXCEPT, $query);
	}


	private function addCombine(string $type, string|Db\Sql $query): static
	{
		$this->params[self::PARAM_COMBINE_QUERIES][] = [$query, $type];
		return $this;
	}


	/**
	 * @param list<string>|null $columns
	 * @throws Exceptions\QueryException
	 */
	public function insert(string|null $into = null, string|null $alias = null, array|null $columns = []): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_INSERT;

		if ($into !== null) {
			$this->table($into, $alias);
		}

		$this->params[self::PARAM_INSERT_COLUMNS] = $columns;

		return $this;
	}


	/**
	 * @param array<string, mixed> $data
	 * @throws Exceptions\QueryException
	 */
	public function values(array $data): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];

		return $this;
	}


	/**
	 * @param list<array<string, mixed>> $rows
	 * @throws Exceptions\QueryException
	 */
	public function rows(array $rows): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_INSERT;
		$this->params[self::PARAM_ROWS] = \array_merge($this->params[self::PARAM_ROWS], $rows);

		return $this;
	}


	/**
	 * @param string|list<string>|null $columnsOrConstraint
	 * @throws Exceptions\QueryException
	 */
	public function onConflict(string|array|null $columnsOrConstraint = null, string|Db\Sql|null $where = null): static
	{
		$this->resetQuery();

		if (\is_string($columnsOrConstraint) && ($where !== null)) {
			throw Exceptions\QueryException::onConflictWhereNotForConstraint();
		}

		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_COLUMNS_OR_CONSTRAINT] = $columnsOrConstraint ?? false;
		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_WHERE] = $where === null ? null : Condition::createAnd()->add($where);

		return $this;
	}


	/**
	 * @param array<int|string, string|Db\Sql> $set
	 * @throws Exceptions\QueryException
	 */
	public function doUpdate(array $set, string|Db\Sql|null $where = null): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_DO] = $set;
		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_DO_WHERE] = $where === null ? null : Condition::createAnd()->add($where);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function doNothing(): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_DO] = false;
		$this->params[self::PARAM_INSERT_ONCONFLICT][self::INSERT_ONCONFLICT_DO_WHERE] = null;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function update(string|null $table = null, string|null $alias = null): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_UPDATE;

		if ($table !== null) {
			$this->table($table, $alias);
		}

		return $this;
	}


	/**
	 * @param array<string, mixed> $data
	 * @throws Exceptions\QueryException
	 */
	public function set(array $data): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_UPDATE;
		$this->params[self::PARAM_DATA] = $data + $this->params[self::PARAM_DATA];

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function delete(string|null $from = null, string|null $alias = null): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_DELETE;

		if ($from !== null) {
			$this->table($from, $alias);
		}

		return $this;
	}


	/**
	 * @param array<int|string, string|int|Db\Sql> $returning
	 * @throws Exceptions\QueryException
	 */
	public function returning(array $returning): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_RETURNING] = \array_merge($this->params[self::PARAM_RETURNING], $returning);

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function merge(string|null $into = null, string|null $alias = null): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_MERGE;

		if ($into !== null) {
			$this->table($into, $alias);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function using(
		string|Db\Sql $dataSource,
		string|null $alias = null,
		string|Db\Sql|null $onCondition = null,
	): static
	{
		return $this->addTable(self::TABLE_TYPE_USING, $dataSource, $alias, $onCondition);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whenMatched(string|Db\Sql $then, string|Db\Sql|null $condition = null): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_MERGE][] = [
			self::MERGE_WHEN_MATCHED,
			$then,
			$condition === null ? null : Condition::createAnd()->add($condition),
		];

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function whenNotMatched(string|Db\Sql $then, string|Db\Sql|null $condition = null): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_MERGE][] = [
			self::MERGE_WHEN_NOT_MATCHED,
			$then,
			$condition === null ? null : Condition::createAnd()->add($condition),
		];

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function truncate(string|null $table = null): static
	{
		$this->resetQuery();

		$this->queryType = self::QUERY_TRUNCATE;

		if ($table !== null) {
			$this->table($table);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function with(
		string $as,
		string|Db\Sql $query,
		string|null $suffix = null,
		bool $notMaterialized = false,
	): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_WITH][self::WITH_QUERIES][$as] = $query;

		if ($suffix !== null) {
			$this->params[self::PARAM_WITH][self::WITH_QUERIES_SUFFIX][$as] = $suffix;
		}

		if ($notMaterialized) {
			$this->params[self::PARAM_WITH][self::WITH_QUERIES_NOT_MATERIALIZED][$as] = true;
		}

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function recursive(): static
	{
		$this->resetQuery();

		$this->params[self::PARAM_WITH][self::WITH_RECURSIVE] = true;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function prefix(string $queryPrefix, mixed ...$params): static
	{
		$this->resetQuery();

		\array_unshift($params, $queryPrefix);
		$this->params[self::PARAM_PREFIX][] = $params;

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function suffix(string $querySuffix, mixed ...$params): static
	{
		$this->resetQuery();

		\array_unshift($params, $querySuffix);
		$this->params[self::PARAM_SUFFIX][] = $params;

		return $this;
	}


	public function has(string $param): bool
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\QueryException::nonExistingQueryParam($param, \array_keys(self::DEFAULT_PARAMS));
		}

		return $this->params[$param] !== self::DEFAULT_PARAMS[$param];
	}


	protected function get(string $param): mixed
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\QueryException::nonExistingQueryParam($param, \array_keys(self::DEFAULT_PARAMS));
		}

		return $this->params[$param];
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function reset(string $param): static
	{
		if (!\array_key_exists($param, self::DEFAULT_PARAMS)) {
			throw Exceptions\QueryException::nonExistingQueryParam($param, \array_keys(self::DEFAULT_PARAMS));
		}

		$this->resetQuery();

		$this->params[$param] = self::DEFAULT_PARAMS[$param];

		return $this;
	}


	protected function resetQuery(): void
	{
		$this->sqlDefinition = null;
		$this->dbQuery = null;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	private function checkAlias(mixed $data, string|null $alias): void
	{
		if ((($data instanceof Db\Sql)) && ($alias === null)) {
			throw Exceptions\QueryException::sqlMustHaveAlias();
		}
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getSqlDefinition(): Db\SqlDefinition
	{
		if ($this->sqlDefinition === null) {
			$this->sqlDefinition = $this->queryBuilder->createSqlDefinition($this->queryType, $this->params);
		}

		return $this->sqlDefinition;
	}


	public function toDbQuery(): Db\Query
	{
		if ($this->dbQuery === null) {
			$this->dbQuery = Db\SqlDefinition::createQuery($this->getSqlDefinition());
		}

		return $this->dbQuery;
	}


	public function __clone()
	{
		$this->resetQuery();

		foreach ($this->params[self::PARAM_ON_CONDITIONS] as $alias => $joinCondition) {
			$this->params[self::PARAM_ON_CONDITIONS][$alias] = clone $joinCondition;
		}

		if ($this->params[self::PARAM_WHERE] !== null) {
			$this->params[self::PARAM_WHERE] = clone $this->params[self::PARAM_WHERE];
		}

		if ($this->params[self::PARAM_HAVING] !== null) {
			$this->params[self::PARAM_HAVING] = clone $this->params[self::PARAM_HAVING];
		}
	}

}
