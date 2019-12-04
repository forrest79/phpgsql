<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class QueryBuilder
{
	private const TABLE_NAME = 0;
	private const TABLE_TYPE = 1;

	/** @var string */
	private $queryType;

	/** @var array */
	private $params;


	public function __construct(string $queryType, array $params)
	{
		$this->queryType = $queryType;
		$this->params = $params;
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function createQuery(): Db\Query
	{
		$params = [];

		$sql = $this->getPrefixSuffix(Fluent::PARAM_PREFIX, $params);

		if ($this->queryType === Fluent::QUERY_SELECT) {
			$sql .= $this->createSelect($params) . $this->getPrefixSuffix(Fluent::PARAM_SUFFIX, $params);
		} else if ($this->queryType === Fluent::QUERY_INSERT) {
			$sql .= $this->createInsert($params);
		} else if ($this->queryType === Fluent::QUERY_UPDATE) {
			$sql .= $this->createUpdate($params);
		} else if ($this->queryType === Fluent::QUERY_DELETE) {
			$sql .= $this->createDelete($params);
		} else if ($this->queryType === Fluent::QUERY_TRUNCATE) {
			$sql .= $this->createTruncate() . $this->getPrefixSuffix(Fluent::PARAM_SUFFIX, $params);
		} else {
			throw Exceptions\QueryBuilderException::badQueryType($this->queryType);
		}

		return new Db\Query($sql, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createSelect(array &$params): string
	{
		return 'SELECT ' .
			$this->getSelectDistinct() .
			$this->getSelectColumns($params) .
			$this->getFrom($params) .
			$this->getJoins($params) .
			$this->getWhere($params) .
			$this->getGroupBy() .
			$this->getHaving($params) .
			$this->getOrderBy($params) .
			$this->getLimit($params) .
			$this->getOffset($params) .
			$this->combine($params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createInsert(array &$params): string
	{
		$mainTableAlias = $this->getMainTableAlias();

		$insert = \sprintf('INSERT %s', $this->processTable(
			'INTO',
			$this->params[Fluent::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
			$mainTableAlias,
			$params
		));

		$columns = [];
		$rows = [];
		if ($this->params[Fluent::PARAM_DATA] !== []) {
			$values = [];
			foreach ($this->params[Fluent::PARAM_DATA] as $column => $value) {
				$columns[] = $column;
				$values[] = '?';
				$params[] = $value;
			}
			$rows[] = \implode(', ', $values);
		} else if ($this->params[Fluent::PARAM_ROWS] !== []) {
			$columns = $this->params[Fluent::PARAM_INSERT_COLUMNS];
			foreach ($this->params[Fluent::PARAM_ROWS] as $row) {
				$values = [];
				$fillColumns = $columns === [];
				foreach ($row as $column => $value) {
					if ($fillColumns) {
						$columns[] = $column;
					}
					$values[] = '?';
					$params[] = $value;
				}
				$rows[] = \implode(', ', $values);
			}
		} else if ($this->params[Fluent::PARAM_SELECT] !== []) {
			$columns = $this->params[Fluent::PARAM_INSERT_COLUMNS];
		} else {
			throw Exceptions\QueryBuilderException::noDataToInsert();
		}

		if ($this->params[Fluent::PARAM_SELECT] !== []) {
			$data = ' SELECT ' .
				$this->getSelectDistinct() .
				($columns === [] ? $this->getSelectColumns($params, $columns) : $this->getSelectColumns($params)) .
				$this->getFrom($params, FALSE) .
				$this->getJoins($params) .
				$this->getWhere($params) .
				$this->getGroupBy() .
				$this->getHaving($params) .
				$this->combine($params);
		} else {
			$data = \sprintf(' VALUES(%s)', \implode('), (', $rows));
		}

		return $insert .
			\sprintf('(%s)', \implode(', ', $columns)) .
			$data .
			$this->getPrefixSuffix(Fluent::PARAM_SUFFIX, $params) .
			$this->getReturning($params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createUpdate(array &$params): string
	{
		if ($this->params[Fluent::PARAM_DATA] === []) {
			throw Exceptions\QueryBuilderException::noDataToUpdate();
		}

		$mainTableAlias = $this->getMainTableAlias();

		$set = [];
		foreach ($this->params[Fluent::PARAM_DATA] as $column => $value) {
			$set[] = \sprintf('%s = ?', $column);
			$params[] = $value;
		}

		return \sprintf('UPDATE %s SET %s', $this->processTable(
				NULL,
				$this->params[Fluent::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
				$mainTableAlias,
				$params
			), \implode(', ', $set)) .
			$this->getFrom($params, FALSE) .
			$this->getJoins($params) .
			$this->getWhere($params) .
			$this->getPrefixSuffix(Fluent::PARAM_SUFFIX, $params) .
			$this->getReturning($params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createDelete(array &$params): string
	{
		$mainTableAlias = $this->getMainTableAlias();
		return \sprintf('DELETE %s', $this->processTable(
				'FROM',
				$this->params[Fluent::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
				$mainTableAlias,
				$params
			)) .
			$this->getWhere($params) .
			$this->getPrefixSuffix(Fluent::PARAM_SUFFIX, $params) .
			$this->getReturning($params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createTruncate(): string
	{
		return \sprintf('TRUNCATE %s', $this->params[Fluent::PARAM_TABLES][$this->getMainTableAlias()][self::TABLE_NAME]);
	}


	private function getSelectDistinct(): string
	{
		return $this->params[Fluent::PARAM_DISTINCT] === TRUE ? 'DISTINCT ' : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getSelectColumns(array &$params, ?array &$columnNames = NULL): string
	{
		if ($this->params[Fluent::PARAM_SELECT] === []) {
			throw Exceptions\QueryBuilderException::noColumnsToSelect();
		}

		$columns = [];
		foreach ($this->params[Fluent::PARAM_SELECT] as $key => $value) {
			if ($value instanceof Db\Queryable) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Fluent) {
				$params[] = $value->getQuery();
				$value = '(?)';
			}
			if ($columnNames !== NULL) {
				$columnNames[] = \is_int($key) ? $value : $key;
			}
			$columns[] = \sprintf('%s%s', $value, \is_int($key) ? '' : \sprintf(' AS %s', $key));
		}

		return \implode(', ', $columns);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getFrom(array &$params, bool $useMainTable = TRUE): string
	{
		$from = [];

		if ($useMainTable === TRUE) {
			$mainTableAlias = $this->params[Fluent::PARAM_TABLE_TYPES][Fluent::TABLE_TYPE_MAIN];
			if ($mainTableAlias !== NULL) {
				$from[] = $this->processTable(
					'FROM',
					$this->params[Fluent::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
					$mainTableAlias,
					$params
				);
			}
		}

		foreach ($this->params[Fluent::PARAM_TABLE_TYPES][Fluent::TABLE_TYPE_FROM] as $tableAlias) {
			$from[] = $this->processTable(
				'FROM',
				$this->params[Fluent::PARAM_TABLES][$tableAlias][self::TABLE_NAME],
				$tableAlias,
				$params
			);
		}

		return $from !== [] ? (' ' . \implode(' ', $from)) : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getJoins(array &$params): string
	{
		$joins = [];

		foreach ($this->params[Fluent::PARAM_TABLE_TYPES][Fluent::TABLE_TYPE_JOINS] as $tableAlias) {
			$joinType = $this->params[Fluent::PARAM_TABLES][$tableAlias][self::TABLE_TYPE];

			$table = $this->processTable(
				$joinType,
				$this->params[Fluent::PARAM_TABLES][$tableAlias][self::TABLE_NAME],
				$tableAlias,
				$params
			);

			if ($joinType === Fluent::JOIN_CROSS) {
				$joins[] = $table;
			} else {
				if (!isset($this->params[Fluent::PARAM_JOIN_CONDITIONS][$tableAlias])) {
					throw Exceptions\QueryBuilderException::noJoinConditions($tableAlias);
				}

				$joins[] = \sprintf(
					'%s ON %s',
					$table,
					$this->processComplex(Complex::createAnd($this->params[Fluent::PARAM_JOIN_CONDITIONS][$tableAlias]), $params)
				);
			}
		}

		return $joins !== [] ? (' ' . \implode(' ', $joins)) : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getWhere(array &$params): string
	{
		$where = $this->params[Fluent::PARAM_WHERE];

		if ($where === []) {
			return '';
		}

		return \sprintf(' WHERE %s', $this->processComplex(Complex::createAnd($where), $params));
	}


	private function getGroupBy(): string
	{
		return $this->params[Fluent::PARAM_GROUPBY] === [] ? '' : \sprintf(' GROUP BY %s', \implode(', ', $this->params[Fluent::PARAM_GROUPBY]));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getHaving(array &$params): string
	{
		$having = $this->params[Fluent::PARAM_HAVING];

		if ($having === []) {
			return '';
		}

		return \sprintf(' HAVING %s', $this->processComplex(Complex::createAnd($having), $params));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getOrderBy(array &$params): string
	{
		$orderBy = $this->params[Fluent::PARAM_ORDERBY];

		if ($orderBy === []) {
			return '';
		}

		$columns = [];
		foreach ($orderBy as $value) {
			if ($value instanceof Db\Queryable) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Fluent) {
				$params[] = $value->getQuery();
				$value = '(?)';
			}
			$columns[] = $value;
		}

		return \sprintf(' ORDER BY %s', \implode(', ', $columns));
	}


	private function getLimit(array &$params): string
	{
		$limit = $this->params[Fluent::PARAM_LIMIT];

		if ($limit === NULL) {
			return '';
		}

		$params[] = $limit;

		return ' LIMIT ?';
	}


	private function getOffset(array &$params): string
	{
		$offset = $this->params[Fluent::PARAM_OFFSET];

		if ($offset === NULL) {
			return '';
		}

		$params[] = $offset;

		return ' OFFSET ?';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getPrefixSuffix(string $type, array &$params): string
	{
		$items = $this->params[$type] ?? [];

		if ($items === []) {
			return '';
		}

		$processedItems = [];
		foreach ($items as $itemParams) {
			$item = \array_shift($itemParams);

			foreach ($itemParams as $param) {
				if ($param instanceof Fluent) {
					$param = $param->getQuery();
				}
				$params[] = $param;
			}

			$processedItems[] = $item;
		}

		if ($type === Fluent::PARAM_PREFIX) {
			return \sprintf('%s ', \implode(' ', $processedItems));
		} else if ($type === Fluent::PARAM_SUFFIX) {
			return \sprintf(' %s', \implode(' ', $processedItems));
		}

		throw Exceptions\FluentException::badParam('$type', $type, [Fluent::PARAM_PREFIX, Fluent::PARAM_SUFFIX]);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function combine(array &$params): string
	{
		$combineQueries = $this->params[Fluent::PARAM_COMBINE_QUERIES];

		if ($combineQueries === []) {
			return '';
		}

		$combines = [];
		foreach ($combineQueries as $combineQuery) {
			[$query, $type] = $combineQuery;

			if ($query instanceof Db\Queryable) {
				$params[] = $query;
				$query = '?';
			} else if ($query instanceof Fluent) {
				$params[] = $query->getQuery();
				$query = '?';
			}

			$combines[] = \sprintf('%s (%s)', $type, $query);
		}

		return ' ' . \implode(' ', $combines);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getReturning(array &$params): string
	{
		if ($this->params[Fluent::PARAM_RETURNING] === []) {
			return '';
		}
		$columns = [];
		foreach ($this->params[Fluent::PARAM_RETURNING] as $key => $value) {
			if ($value instanceof Db\Queryable) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Fluent) {
				$params[] = $value->getQuery();
				$value = '(?)';
			}
			$columns[] = \sprintf('%s%s', $value, \is_int($key) ? '' : \sprintf(' AS %s', $key));
		}
		return \sprintf(' RETURNING %s', \implode(', ', $columns));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getMainTableAlias(): string
	{
		if ($this->params[Fluent::PARAM_TABLE_TYPES][Fluent::TABLE_TYPE_MAIN] === NULL) {
			throw Exceptions\QueryBuilderException::noMainTable();
		}
		return $this->params[Fluent::PARAM_TABLE_TYPES][Fluent::TABLE_TYPE_MAIN];
	}


	/**
	 * @param string|NULL $type
	 * @param string|Db\Queryable|Fluent $table
	 * @param string $alias
	 * @param array $params
	 * @return string
	 */
	private function processTable(?string $type, $table, string $alias, array &$params): string
	{
		if ($table instanceof Db\Queryable) {
			$params[] = $table;
			if ($table instanceof Db\Query) {
				$table = '(?)';
			} else if ($table instanceof Db\Literal) {
				$table = '?';
			} else {
				throw Exceptions\QueryBuilderException::badQueryable($table);
			}
		} else if ($table instanceof Fluent) {
			$params[] = $table->getQuery();
			$table = '(?)';
		}
		return (($type === NULL) ? '' : ($type . ' ')) . ($table === $alias ? $table : \sprintf('%s AS %s', $table, $alias));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function processComplex(Complex $complex, array &$params): string
	{
		$conditions = $complex->getConditions();
		$withoutParentheses = \count($conditions) === 1;
		$processedConditions = [];
		foreach ($conditions as $conditionParams) {
			if ($conditionParams instanceof Complex) {
				$condition = \sprintf($withoutParentheses === TRUE ? '%s' : '(%s)', $this->processComplex($conditionParams, $params));
			} else {
				$condition = \array_shift($conditionParams);
				$cnt = \preg_match_all('/(?<!\\\\)\?/', $condition);
				$cntParams = \count($conditionParams);
				if (($cnt === 0) && ($cntParams === 1)) {
					$param = \reset($conditionParams);
					if (\is_array($param) || ($param instanceof Db\Queryable) || ($param instanceof Fluent)) {
						$condition = \sprintf('%s IN (?)', $condition);
					} else if ($param === NULL) {
						$condition = \sprintf('%s IS NULL', $condition);
						\array_shift($conditionParams);
					} else {
						$condition = \sprintf('%s = ?', $condition);
					}
					$cnt = 1;
				}

				if ($cnt !== $cntParams) {
					throw Exceptions\QueryBuilderException::badParamsCount($condition, $cnt, $cntParams);
				}

				if ($withoutParentheses === FALSE) {
					$condition = \sprintf('(%s)', $condition);
				}

				foreach ($conditionParams as $param) {
					if ($param instanceof Fluent) {
						$param = $param->getQuery();
					}
					$params[] = $param;
				}
			}

			$processedConditions[] = $condition;
		}

		return \implode(\sprintf(' %s ', $complex->getType()), $processedConditions);
	}

}
