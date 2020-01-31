<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class QueryBuilder
{
	private const TABLE_NAME = 0;
	private const TABLE_TYPE = 1;


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	public function createSqlQuery(string $queryType, array $queryParams): Db\Sql\Query
	{
		$params = [];

		$sql = $this->getPrefixSuffix($queryParams, Query::PARAM_PREFIX, $params);

		if ($queryType === Query::QUERY_SELECT) {
			$sql .= $this->createSelect($queryParams, $params) . $this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params);
		} else if ($queryType === Query::QUERY_INSERT) {
			$sql .= $this->createInsert($queryParams, $params);
		} else if ($queryType === Query::QUERY_UPDATE) {
			$sql .= $this->createUpdate($queryParams, $params);
		} else if ($queryType === Query::QUERY_DELETE) {
			$sql .= $this->createDelete($queryParams, $params);
		} else if ($queryType === Query::QUERY_TRUNCATE) {
			$sql .= $this->createTruncate($queryParams) . $this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params);
		} else {
			throw Exceptions\QueryBuilderException::badQueryType($queryType);
		}

		return $this->prepareSqlQuery($sql, $params);
	}


	protected function prepareSqlQuery(string $sql, array $params): Db\Sql\Query
	{
		return new Db\Sql\Query($sql, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createSelect(array $queryParams, array &$params): string
	{
		return 'SELECT ' .
			$this->getSelectDistinct($queryParams) .
			$this->getSelectColumns($queryParams, $params) .
			$this->getFrom($queryParams, $params) .
			$this->getJoins($queryParams, $params) .
			$this->getWhere($queryParams, $params) .
			$this->getGroupBy($queryParams) .
			$this->getHaving($queryParams, $params) .
			$this->getOrderBy($queryParams, $params) .
			$this->getLimit($queryParams, $params) .
			$this->getOffset($queryParams, $params) .
			$this->combine($queryParams, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createInsert(array $queryParams, array &$params): string
	{
		$mainTableAlias = $this->getMainTableAlias($queryParams);

		$insert = \sprintf('INSERT %s', $this->processTable(
			'INTO',
			$queryParams[Query::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
			$mainTableAlias,
			$params
		));

		$columns = [];
		$rows = [];
		if ($queryParams[Query::PARAM_DATA] !== []) {
			$values = [];
			foreach ($queryParams[Query::PARAM_DATA] as $column => $value) {
				$columns[] = $column;
				$values[] = '?';
				$params[] = $value;
			}
			$rows[] = \implode(', ', $values);
		} else if ($queryParams[Query::PARAM_ROWS] !== []) {
			$columns = $queryParams[Query::PARAM_INSERT_COLUMNS];
			foreach ($queryParams[Query::PARAM_ROWS] as $row) {
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
		} else if ($queryParams[Query::PARAM_SELECT] !== []) {
			$columns = $queryParams[Query::PARAM_INSERT_COLUMNS];
		} else {
			throw Exceptions\QueryBuilderException::noDataToInsert();
		}

		if ($queryParams[Query::PARAM_SELECT] !== []) {
			$data = ' SELECT ' .
				$this->getSelectDistinct($queryParams) .
				($columns === [] ? $this->getSelectColumns($queryParams, $params, $columns) : $this->getSelectColumns($queryParams, $params)) .
				$this->getFrom($queryParams, $params, FALSE) .
				$this->getJoins($queryParams, $params) .
				$this->getWhere($queryParams, $params) .
				$this->getGroupBy($queryParams) .
				$this->getHaving($queryParams, $params) .
				$this->combine($queryParams, $params);
		} else {
			$data = \sprintf(' VALUES(%s)', \implode('), (', $rows));
		}

		return $insert .
			\sprintf('(%s)', \implode(', ', $columns)) .
			$data .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createUpdate(array $queryParams, array &$params): string
	{
		if ($queryParams[Query::PARAM_DATA] === []) {
			throw Exceptions\QueryBuilderException::noDataToUpdate();
		}

		$mainTableAlias = $this->getMainTableAlias($queryParams);

		$set = [];
		foreach ($queryParams[Query::PARAM_DATA] as $column => $value) {
			$set[] = \sprintf('%s = ?', $column);
			$params[] = $value;
		}

		return \sprintf('UPDATE %s SET %s', $this->processTable(
				NULL,
				$queryParams[Query::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
				$mainTableAlias,
				$params
			), \implode(', ', $set)) .
			$this->getFrom($queryParams, $params, FALSE) .
			$this->getJoins($queryParams, $params) .
			$this->getWhere($queryParams, $params) .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createDelete(array $queryParams, array &$params): string
	{
		$mainTableAlias = $this->getMainTableAlias($queryParams);
		return \sprintf('DELETE %s', $this->processTable(
				'FROM',
				$queryParams[Query::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
				$mainTableAlias,
				$params
			)) .
			$this->getWhere($queryParams, $params) .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function createTruncate(array $queryParams): string
	{
		return \sprintf('TRUNCATE %s', $queryParams[Query::PARAM_TABLES][$this->getMainTableAlias($queryParams)][self::TABLE_NAME]);
	}


	private function getSelectDistinct(array $queryParams): string
	{
		return $queryParams[Query::PARAM_DISTINCT] === TRUE ? 'DISTINCT ' : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getSelectColumns(array $queryParams, array &$params, ?array &$columnNames = NULL): string
	{
		if ($queryParams[Query::PARAM_SELECT] === []) {
			throw Exceptions\QueryBuilderException::noColumnsToSelect();
		}

		$columns = [];
		foreach ($queryParams[Query::PARAM_SELECT] as $key => $value) {
			if ($value instanceof Db\Sql) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Query) {
				$params[] = $value->createSqlQuery();
				$value = '(?)';
			}
			if ($columnNames !== NULL) {
				$columnNames[] = \is_int($key) ? $value : $key;
			}
			$columns[] = $value . (\is_int($key) ? '' : \sprintf(' AS "%s"', $key));
		}

		return \implode(', ', $columns);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getFrom(array $queryParams, array &$params, bool $useMainTable = TRUE): string
	{
		$from = [];

		if ($useMainTable === TRUE) {
			$mainTableAlias = $queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_MAIN];
			if ($mainTableAlias !== NULL) {
				$from[] = $this->processTable(
					NULL,
					$queryParams[Query::PARAM_TABLES][$mainTableAlias][self::TABLE_NAME],
					$mainTableAlias,
					$params
				);
			}
		}

		foreach ($queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_FROM] as $tableAlias) {
			$from[] = $this->processTable(
				NULL,
				$queryParams[Query::PARAM_TABLES][$tableAlias][self::TABLE_NAME],
				$tableAlias,
				$params
			);
		}

		return $from !== [] ? (' FROM ' . \implode(', ', $from)) : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getJoins(array $queryParams, array &$params): string
	{
		$joins = [];

		foreach ($queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_JOINS] as $tableAlias) {
			$joinType = $queryParams[Query::PARAM_TABLES][$tableAlias][self::TABLE_TYPE];

			$table = $this->processTable(
				$joinType,
				$queryParams[Query::PARAM_TABLES][$tableAlias][self::TABLE_NAME],
				$tableAlias,
				$params
			);

			if ($joinType === Query::JOIN_CROSS) {
				$joins[] = $table;
			} else {
				if (!isset($queryParams[Query::PARAM_JOIN_CONDITIONS][$tableAlias])) {
					throw Exceptions\QueryBuilderException::noJoinConditions($tableAlias);
				}

				$joins[] = \sprintf(
					'%s ON %s',
					$table,
					$this->processComplex(Complex::createAnd($queryParams[Query::PARAM_JOIN_CONDITIONS][$tableAlias]), $params)
				);
			}
		}

		return $joins !== [] ? (' ' . \implode(' ', $joins)) : '';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getWhere(array $queryParams, array &$params): string
	{
		$where = $queryParams[Query::PARAM_WHERE];

		if ($where === []) {
			return '';
		}

		return \sprintf(' WHERE %s', $this->processComplex(Complex::createAnd($where), $params));
	}


	private function getGroupBy(array $queryParams): string
	{
		return $queryParams[Query::PARAM_GROUPBY] === []
			? ''
			: \sprintf(' GROUP BY %s', \implode(', ', $queryParams[Query::PARAM_GROUPBY]));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getHaving(array $queryParams, array &$params): string
	{
		$having = $queryParams[Query::PARAM_HAVING];

		if ($having === []) {
			return '';
		}

		return \sprintf(' HAVING %s', $this->processComplex(Complex::createAnd($having), $params));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getOrderBy(array $queryParams, array &$params): string
	{
		$orderBy = $queryParams[Query::PARAM_ORDERBY];

		if ($orderBy === []) {
			return '';
		}

		$columns = [];
		foreach ($orderBy as $value) {
			if ($value instanceof Db\Sql) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Query) {
				$params[] = $value->createSqlQuery();
				$value = '(?)';
			}
			$columns[] = $value;
		}

		return \sprintf(' ORDER BY %s', \implode(', ', $columns));
	}


	private function getLimit(array $queryParams, array &$params): string
	{
		$limit = $queryParams[Query::PARAM_LIMIT];

		if ($limit === NULL) {
			return '';
		}

		$params[] = $limit;

		return ' LIMIT ?';
	}


	private function getOffset(array $queryParams, array &$params): string
	{
		$offset = $queryParams[Query::PARAM_OFFSET];

		if ($offset === NULL) {
			return '';
		}

		$params[] = $offset;

		return ' OFFSET ?';
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getPrefixSuffix(array $queryParams, string $type, array &$params): string
	{
		$items = $queryParams[$type] ?? [];

		if ($items === []) {
			return '';
		}

		$processedItems = [];
		foreach ($items as $itemParams) {
			$item = \array_shift($itemParams);

			foreach ($itemParams as $param) {
				if ($param instanceof Query) {
					$param = $param->createSqlQuery();
				}
				$params[] = $param;
			}

			$processedItems[] = $item;
		}

		if ($type === Query::PARAM_PREFIX) {
			return \sprintf('%s ', \implode(' ', $processedItems));
		} else if ($type === Query::PARAM_SUFFIX) {
			return \sprintf(' %s', \implode(' ', $processedItems));
		}

		throw Exceptions\QueryException::badParam('$type', $type, [Query::PARAM_PREFIX, Query::PARAM_SUFFIX]);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function combine(array $queryParams, array &$params): string
	{
		$combineQueries = $queryParams[Query::PARAM_COMBINE_QUERIES];

		if ($combineQueries === []) {
			return '';
		}

		$combines = [];
		foreach ($combineQueries as $combineQuery) {
			[$query, $type] = $combineQuery;

			if ($query instanceof Db\Sql) {
				$params[] = $query;
				$query = '?';
			} else if ($query instanceof Query) {
				$params[] = $query->createSqlQuery();
				$query = '?';
			}

			$combines[] = \sprintf('%s (%s)', $type, $query);
		}

		return ' ' . \implode(' ', $combines);
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getReturning(array $queryParams, array &$params): string
	{
		if ($queryParams[Query::PARAM_RETURNING] === []) {
			return '';
		}
		$columns = [];
		foreach ($queryParams[Query::PARAM_RETURNING] as $key => $value) {
			if ($value instanceof Db\Sql) {
				$params[] = $value;
				$value = '(?)';
			} else if ($value instanceof Query) {
				$params[] = $value->createSqlQuery();
				$value = '(?)';
			}
			$columns[] = $value . (\is_int($key) ? '' : \sprintf(' AS "%s"', $key));
		}
		return \sprintf(' RETURNING %s', \implode(', ', $columns));
	}


	/**
	 * @throws Exceptions\QueryBuilderException
	 */
	private function getMainTableAlias(array $queryParams): string
	{
		if ($queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_MAIN] === NULL) {
			throw Exceptions\QueryBuilderException::noMainTable();
		}
		return $queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_MAIN];
	}


	/**
	 * @param string|NULL $type
	 * @param string|Db\Sql|Query $table
	 * @param string $alias
	 * @param array $params
	 * @return string
	 */
	private function processTable(?string $type, $table, string $alias, array &$params): string
	{
		if ($table instanceof Db\Sql) {
			$params[] = $table;
			if ($table instanceof Db\Sql\Query) {
				$table = '(?)';
			} else {
				$table = '?';
			}
		} else if ($table instanceof Query) {
			$params[] = $table->createSqlQuery();
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
					if (\is_array($param) || ($param instanceof Db\Sql) || ($param instanceof Query)) {
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
					if ($param instanceof Query) {
						$param = $param->createSqlQuery();
					}
					$params[] = $param;
				}
			}

			$processedConditions[] = $condition;
		}

		return \implode(\sprintf(' %s ', $complex->getType()), $processedConditions);
	}

}
