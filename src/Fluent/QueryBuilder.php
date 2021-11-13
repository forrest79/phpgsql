<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @phpstan-type QueryParams array{select: array<int|string, string|int|Query|Db\Sql>, distinct: bool, tables: array<string, array{0: string, 1: string}>, table-types: array{main: string|NULL, from: array<string>, joins: array<string>}, join-conditions: array<string, Complex>, where: Complex|NULL, groupBy: array<string>, having: Complex|NULL, orderBy: array<string|Db\Sql|Query>, limit: int|NULL, offset: int|NULL, combine-queries: array<array{0: string|Query|Db\Sql, 1: string}>, insert-columns: array<string>, returning: array<int|string, string|int|Query|Db\Sql>, data: array<string, mixed>, rows: array<int, array<string, mixed>>, prefix: array<array<mixed>>, suffix: array<array<mixed>>}
 */
class QueryBuilder
{
	private const TABLE_NAME = 0;
	private const TABLE_TYPE = 1;


	/**
	 * @param array<string, mixed> $queryParams
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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


	/**
	 * @param array<mixed> $params
	 */
	protected function prepareSqlQuery(string $sql, array $params): Db\Sql\Query
	{
		return new Db\Sql\Query($sql, $params);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @param array<int, string>|NULL $insertSelectColumnNames
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function createSelect(array $queryParams, array &$params, ?array &$insertSelectColumnNames = NULL): string
	{
		return 'SELECT ' .
			$this->getSelectDistinct($queryParams) .
			$this->getSelectColumns($queryParams, $params, $insertSelectColumnNames) .
			$this->getFrom($queryParams, $params, $insertSelectColumnNames === NULL) .
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
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function createInsert(array $queryParams, array &$params): string
	{
		['table' => $mainTable, 'alias' => $mainTableAlias] = $this->getMainTableMetadata($queryParams);

		$insert = 'INSERT ' . $this->processTable(
			'INTO',
			$mainTable,
			$mainTableAlias,
			$params
		);

		$columns = [];
		$rows = [];
		if ($queryParams[Query::PARAM_DATA] !== []) {
			$values = [];
			foreach ($queryParams[Query::PARAM_DATA] as $column => $value) {
				$columns[] = $column;
				self::prepareRow($value, $values, $params);
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
					self::prepareRow($value, $values, $params);
				}
				$rows[] = \implode(', ', $values);
			}
		} else if ($queryParams[Query::PARAM_SELECT] !== []) {
			$columns = $queryParams[Query::PARAM_INSERT_COLUMNS];
		} else {
			throw Exceptions\QueryBuilderException::noDataToInsert();
		}

		if ($queryParams[Query::PARAM_SELECT] !== []) {
			$selectColumns = [];
			$data = ' ' . $this->createSelect($queryParams, $params, $selectColumns);
			if ($columns === []) {
				$columns = $selectColumns;
			}
		} else {
			$data = ' VALUES(' . \implode('), (', $rows) . ')';
		}

		return $insert .
			'(' . \implode(', ', $columns) . ')' .
			$data .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @param Db\Sql\Query|Query|mixed $value
	 * @param array<string> $values
	 * @param array<Db\Sql\Query|Query|mixed> $params
	 */
	private static function prepareRow($value, array &$values, array &$params): void
	{
		if ($value instanceof Db\Sql\Query) {
			$values[] = '(?)';
			$params[] = $value;
		} else if ($value instanceof Query) {
			$values[] = '(?)';
			$params[] = $value->createSqlQuery();
		} else if (\is_array($value)) {
			throw Exceptions\QueryBuilderException::dataCantContainArray();
		} else {
			$values[] = '?';
			$params[] = $value;
		}
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function createUpdate(array $queryParams, array &$params): string
	{
		if ($queryParams[Query::PARAM_DATA] === []) {
			throw Exceptions\QueryBuilderException::noDataToUpdate();
		}

		['table' => $mainTable, 'alias' => $mainTableAlias] = $this->getMainTableMetadata($queryParams);

		$set = [];
		foreach ($queryParams[Query::PARAM_DATA] as $column => $value) {
			if ($value instanceof Db\Sql\Query) {
				$set[] = $column . ' = (?)';
				$params[] = $value;
			} else if ($value instanceof Query) {
				$set[] = $column . ' = (?)';
				$params[] = $value->createSqlQuery();
			} else if (\is_array($value)) {
				throw Exceptions\QueryBuilderException::dataCantContainArray();
			} else {
				$set[] = $column . ' = ?';
				$params[] = $value;
			}
		}

		return 'UPDATE ' . $this->processTable(
				NULL,
				$mainTable,
				$mainTableAlias,
				$params
			) . ' SET ' . \implode(', ', $set) .
			$this->getFrom($queryParams, $params, FALSE) .
			$this->getJoins($queryParams, $params) .
			$this->getWhere($queryParams, $params) .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function createDelete(array $queryParams, array &$params): string
	{
		['table' => $mainTable, 'alias' => $mainTableAlias] = $this->getMainTableMetadata($queryParams);
		return 'DELETE ' . $this->processTable(
				'FROM',
				$mainTable,
				$mainTableAlias,
				$params
			) .
			$this->getWhere($queryParams, $params) .
			$this->getPrefixSuffix($queryParams, Query::PARAM_SUFFIX, $params) .
			$this->getReturning($queryParams, $params);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function createTruncate(array $queryParams): string
	{
		return 'TRUNCATE ' . $this->getMainTableMetadata($queryParams)['table'];
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @phpstan-param QueryParams $queryParams
	 */
	private function getSelectDistinct(array $queryParams): string
	{
		return $queryParams[Query::PARAM_DISTINCT] === TRUE ? 'DISTINCT ' : '';
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @param array<int, string>|NULL $columnNames
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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
			$columns[] = $value . (\is_int($key) ? '' : (' AS "' . $key . '"'));
		}

		return \implode(', ', $columns);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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

				$joins[] = $table . ' ON ' . $this->processComplex(
					$queryParams[Query::PARAM_JOIN_CONDITIONS][$tableAlias],
					$params
				);
			}
		}

		return $joins !== [] ? (' ' . \implode(' ', $joins)) : '';
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function getWhere(array $queryParams, array &$params): string
	{
		$where = $queryParams[Query::PARAM_WHERE];

		if ($where === NULL) {
			return '';
		}

		return ' WHERE ' . $this->processComplex($where, $params);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @phpstan-param QueryParams $queryParams
	 */
	private function getGroupBy(array $queryParams): string
	{
		return $queryParams[Query::PARAM_GROUPBY] === []
			? ''
			: ' GROUP BY ' . \implode(', ', $queryParams[Query::PARAM_GROUPBY]);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	private function getHaving(array $queryParams, array &$params): string
	{
		$having = $queryParams[Query::PARAM_HAVING];

		if ($having === NULL) {
			return '';
		}

		return ' HAVING ' . $this->processComplex($having, $params);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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

		return ' ORDER BY ' . \implode(', ', $columns);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @phpstan-param QueryParams $queryParams
	 */
	private function getLimit(array $queryParams, array &$params): string
	{
		$limit = $queryParams[Query::PARAM_LIMIT];

		if ($limit === NULL) {
			return '';
		}

		$params[] = $limit;

		return ' LIMIT ?';
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @phpstan-param QueryParams $queryParams
	 */
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
	 * @param array<string, mixed> $queryParams
	 * @param Query::PARAM_PREFIX|Query::PARAM_SUFFIX $type
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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
			return \implode(' ', $processedItems) . ' ';
		} else if ($type === Query::PARAM_SUFFIX) {
			return ' ' . \implode(' ', $processedItems);
		}

		throw Exceptions\QueryBuilderException::badParam('$type', $type, [Query::PARAM_PREFIX, Query::PARAM_SUFFIX]);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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

			$combines[] = $type . ' (' . $query . ')';
		}

		return ' ' . \implode(' ', $combines);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @param array<mixed> $params
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
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
			$columns[] = $value . (\is_int($key) ? '' : (' AS "' . $key . '"'));
		}
		return ' RETURNING ' . \implode(', ', $columns);
	}


	/**
	 * @param array<string, mixed> $queryParams
	 * @return array{table: string, alias: string}
	 * @throws Exceptions\QueryBuilderException
	 * @phpstan-param QueryParams $queryParams
	 */
	final protected function getMainTableMetadata(array $queryParams): array
	{
		if ($queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_MAIN] === NULL) {
			throw Exceptions\QueryBuilderException::noMainTable();
		}
		$alias = $queryParams[Query::PARAM_TABLE_TYPES][Query::TABLE_TYPE_MAIN];

		return ['table' => $queryParams[Query::PARAM_TABLES][$alias][self::TABLE_NAME], 'alias' => $alias];
	}


	/**
	 * @param string|NULL $type
	 * @param string|Db\Sql|Query $table
	 * @param array<mixed> $params
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
		return (($type === NULL) ? '' : ($type . ' ')) . ($table === $alias ? $table : ($table . ' AS ' . $alias));
	}


	/**
	 * @param array<int, mixed> $params
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
				\assert(\is_string($condition)); // first array item is SQL, next are mixed params
				$cnt = \preg_match_all('/(?<!\\\\)\?/', $condition);
				$cntParams = \count($conditionParams);
				if (($cnt === 0) && ($cntParams === 1)) {
					$param = \reset($conditionParams);
					if (\is_array($param) || ($param instanceof Db\Sql) || ($param instanceof Query)) {
						$condition .= ' IN (?)';
					} else if ($param === NULL) {
						$condition .= ' IS NULL';
						\array_shift($conditionParams);
					} else {
						$condition .= ' = ?';
					}
					$cnt = 1;
				}

				if ($cnt !== $cntParams) {
					throw Exceptions\QueryBuilderException::badParamsCount($condition, $cnt, $cntParams);
				}

				if ($withoutParentheses === FALSE) {
					$condition = '(' . $condition . ')';
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

		return \implode(' ' . $complex->getType() . ' ', $processedConditions);
	}

}
