<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class Result implements \Countable
{
	protected PgSql\Result $queryResource;

	private Query $query;

	private RowFactory $rowFactory;

	private DataTypeParser $dataTypeParser;

	/** @var array<int, string>|null */
	private array|null $dataTypesCache;

	/** @var list<\Closure(Row): void> */
	private array $rowFetchMutators = [];

	/** @var array<string, list<callable>> */
	private array $columnsFetchMutators = [];

	private int|null $affectedRows = null;

	/** @var array<string, string>|null */
	private array|null $columnsDataTypes = null;

	private ColumnValueParser|null $columnValueParser = null;


	/**
	 * @param array<int, string>|null $dataTypesCache
	 */
	public function __construct(
		PgSql\Result $queryResource,
		Query $query,
		RowFactory $rowFactory,
		DataTypeParser $dataTypeParser,
		array|null $dataTypesCache,
	)
	{
		$this->queryResource = $queryResource;
		$this->query = $query;
		$this->rowFactory = $rowFactory;
		$this->dataTypeParser = $dataTypeParser;
		$this->dataTypesCache = $dataTypesCache;
	}


	public function setRowFactory(RowFactory $rowFactory): static
	{
		$this->rowFactory = $rowFactory;

		return $this;
	}


	/**
	 * @param \Closure(Row): void $rowFetchMutator
	 */
	public function addRowFetchMutator(\Closure $rowFetchMutator): static
	{
		$this->rowFetchMutators[] = $rowFetchMutator;

		return $this;
	}


	/**
	 * @param non-empty-array<string, callable> $columnsFetchMutator
	 */
	public function addColumnsFetchMutator(array $columnsFetchMutator): static
	{
		foreach ($columnsFetchMutator as $column => $mutator) {
			$this->columnsFetchMutators[$column][] = $mutator;
		}

		return $this;
	}


	/**
	 * @param \Closure(Row): void $rowFetchMutator
	 * @deprecated use addRowFetchMutator() instead
	 */
	public function setRowFetchMutator(\Closure $rowFetchMutator): static
	{
		$this->rowFetchMutators = [];

		return $this->addRowFetchMutator($rowFetchMutator);
	}


	/**
	 * @param non-empty-array<string, callable> $columnsFetchMutator
	 * @deprecated use addColumnsFetchMutator() instead
	 */
	public function setColumnsFetchMutator(array $columnsFetchMutator): static
	{
		$this->columnsFetchMutators = [];

		return $this->addColumnsFetchMutator($columnsFetchMutator);
	}


	public function free(): bool
	{
		return \pg_free_result($this->queryResource);
	}


	public function getResource(): PgSql\Result
	{
		return $this->queryResource;
	}


	public function seek(int $row): bool
	{
		return \pg_result_seek($this->queryResource, $row);
	}


	public function count(): int
	{
		/** @phpstan-var int<0, max> */
		return $this->getRowCount();
	}


	public function getRowCount(): int
	{
		return \pg_num_rows($this->queryResource);
	}


	public function hasRows(): bool
	{
		return $this->getRowCount() > 0;
	}


	public function fetch(): Row|null
	{
		$data = \pg_fetch_assoc($this->queryResource);
		if ($data === false) {
			return null;
		}

		$row = $this->rowFactory->create($this->getColumnValueParser(), $data);

		foreach ($this->rowFetchMutators as $rowFetchMutator) {
			call_user_func($rowFetchMutator, $row);
		}

		return $row;
	}


	/**
	 * Like fetch(), but returns only first field.
	 *
	 * @return mixed value on success, null if no next record
	 */
	public function fetchSingle(): mixed
	{
		$row = $this->fetch();
		if ($row === null) {
			return null;
		}

		$columns = $this->getColumns();
		$firstColumn = $columns[0];

		return $this->applyColumnFetchMutators($this->getColumnFetchMutators($firstColumn), $row[$firstColumn]);
	}


	/**
	 * Fetches all records from table.
	 *
	 * @return list<Row>
	 */
	public function fetchAll(int|null $offset = null, int|null $limit = null): array
	{
		$limit = $limit ?? -1;
		$this->seek($offset ?? 0);
		$row = $this->fetch();
		if ($row === null) {
			return []; // empty result set
		}

		$data = [];
		do {
			if ($limit === 0) {
				break;
			}
			$limit--;

			$data[] = $row;

			$row = $this->fetch();
		} while ($row !== null);

		return $data;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * Examples:
	 * - associative descriptor: col1[]col2
	 *   builds a tree:          $tree[$val1][$index][$val2] = Row
	 * - associative descriptor: col1|col2=col3
	 *   builds a tree:          $tree[$val1][$val2] = $val3
	 * - associative descriptor: col1|col2=[]
	 *   builds a tree:          $tree[$val1][$val2] = Row::toArray()
	 *
	 * @return array<int|string, mixed>
	 * @throws Exceptions\ResultException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchAssoc(string $assocDesc): array
	{
		$parts = \preg_split('#(\[\]|=|\|)#', $assocDesc, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
		if (($parts === false) || ($parts === [])) {
			throw Exceptions\ResultException::fetchAssocBadDescriptor($assocDesc);
		}

		$firstPart = \reset($parts);
		$lastPart = \end($parts);
		if (($firstPart === '=') || ($firstPart === '|') || ($lastPart === '=') || ($lastPart === '|')) {
			throw Exceptions\ResultException::fetchAssocBadDescriptor($assocDesc);
		}

		$this->seek(0);

		$data = [];
		$columnsChecked = false;

		// make associative tree
		while (($row = $this->fetch()) !== null) {
			if (!$columnsChecked) {
				foreach ($parts as $checkPart) {
					if (($checkPart !== '[]') && ($checkPart !== '=') && ($checkPart !== '|') && !$row->hasColumn($checkPart)) {
						throw Exceptions\ResultException::fetchAssocNoColumn($checkPart, $assocDesc);
					}
				}
				$columnsChecked = true;
			}

			$x = &$data;

			// iterative deepening
			foreach ($parts as $i => $part) {
				if ($part === '[]') { // indexed-array node
					$x = &$x[];
				} else if ($part === '=') { // "value" node
					if ($parts[$i + 1] === '[]') { // get Row as array
						$x = $row->toArray();
					} else { // get concrete Row column
						$key = $parts[$i + 1];
						$x = $this->applyColumnFetchMutators($this->getColumnFetchMutators($key), $row->$key);
					}

					continue 2;
				} else if ($part !== '|') { // associative-array node
					$mutators = $this->getColumnFetchMutators($part);
					if ($mutators !== []) {
						$val = $this->applyColumnFetchMutators($mutators, $row->$part);
						if (($val !== null) && !\is_scalar($val)) {
							throw Exceptions\ResultException::fetchMutatorBadReturnType($part, $val);
						}
					} else {
						$val = $row->$part;
						if (($val !== null) && !\is_scalar($val)) {
							throw Exceptions\ResultException::fetchAssocOnlyScalarAsKey($assocDesc, $part, $val);
						}
					}

					$x = &$x[(string) $val];
				}
			}

			if ($x === null) { // build leaf
				$x = $row;
			}
		}

		unset($x);

		assert(is_array($data));
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 *
	 * @return array<int|string, mixed>
	 * @throws Exceptions\ResultException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchPairs(string|null $key = null, string|null $value = null): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if ($row === null) {
			return []; // empty result set
		}

		$data = [];

		if ($value === null) {
			if ($key !== null) {
				throw Exceptions\ResultException::fetchPairsBadColumns();
			}

			// autodetect
			$tmp = \array_keys($row->toArray());
			$key = $tmp[0];
			if (\count($row) < 2) { // indexed-array
				$mutators = $this->getColumnFetchMutators($key);
				do {
					$data[] = $this->applyColumnFetchMutators($mutators, $row[$key]);
					$row = $this->fetch();
				} while ($row !== null);

				return $data;
			}

			$value = $tmp[1];
		} else {
			if ($row->hasColumn($value) === false) {
				throw Exceptions\ResultException::noColumn($value);
			}

			if ($key === null) { // indexed-array
				$mutators = $this->getColumnFetchMutators($value);
				do {
					$data[] = $this->applyColumnFetchMutators($mutators, $row[$value]);
					$row = $this->fetch();
				} while ($row !== null);

				return $data;
			}

			if ($row->hasColumn($key) === false) {
				throw Exceptions\ResultException::noColumn($key);
			}
		}

		$keyMutators = $this->getColumnFetchMutators($key);
		$valueMutators = $this->getColumnFetchMutators($value);

		do {
			if ($keyMutators !== []) {
				$keyValue = $this->applyColumnFetchMutators($keyMutators, $row[$key]);
				if (($keyValue !== null) && !\is_scalar($keyValue)) {
					throw Exceptions\ResultException::fetchMutatorBadReturnType($key, $keyValue);
				}
			} else {
				$keyValue = $row[$key];
				if (($keyValue !== null) && !\is_scalar($keyValue)) {
					throw Exceptions\ResultException::fetchPairsOnlyScalarAsKey($key, $keyValue);
				}
			}

			$data[$keyValue] = $this->applyColumnFetchMutators($valueMutators, $row[$value]);

			$row = $this->fetch();
		} while ($row !== null);

		return $data;
	}


	/**
	 * @return RowIterator<int, Row>
	 */
	public function fetchIterator(): RowIterator
	{
		return new RowIterator($this);
	}


	public function getQuery(): Query
	{
		return $this->query;
	}


	public function getAffectedRows(): int
	{
		if ($this->affectedRows === null) {
			$this->affectedRows = \pg_affected_rows($this->queryResource);
		}

		return $this->affectedRows;
	}


	public function hasAffectedRows(): bool
	{
		return $this->getAffectedRows() > 0;
	}


	/**
	 * @throws Exceptions\ResultException
	 */
	public function getColumnType(string $column): string
	{
		return $this->getColumnsDataTypes()[$column] ?? throw Exceptions\ResultException::noColumn($column);
	}


	/**
	 * @return list<string>
	 */
	public function getColumns(): array
	{
		return \array_keys($this->getColumnsDataTypes());
	}


	/**
	 * @return array<string, bool>|null null = no fetch was called yet
	 */
	public function getParsedColumns(): array|null
	{
		return $this->columnValueParser === null
			? null
			: \array_fill_keys($this->columnValueParser->getParsedColumns(), true) + \array_fill_keys($this->getColumns(), false);
	}


	/**
	 * @return list<callable>
	 */
	private function getColumnFetchMutators(string $column): array
	{
		return $this->columnsFetchMutators[$column] ?? [];
	}


	/**
	 * @param list<callable> $mutators
	 */
	private function applyColumnFetchMutators(array $mutators, mixed $value): mixed
	{
		foreach ($mutators as $mutator) {
			$value = call_user_func($mutator, $value);
		}

		return $value;
	}


	private function getColumnValueParser(): ColumnValueParser
	{
		if ($this->columnValueParser === null) {
			$this->columnValueParser = new ColumnValueParser($this->dataTypeParser, $this->getColumnsDataTypes());
		}

		return $this->columnValueParser;
	}


	/**
	 * @return array<string, string>
	 */
	private function getColumnsDataTypes(): array
	{
		if ($this->columnsDataTypes === null) {
			$this->columnsDataTypes = [];
			$fieldsCnt = \pg_num_fields($this->queryResource);
			for ($i = 0; $i < $fieldsCnt; $i++) {
				$name = \pg_field_name($this->queryResource, $i);

				if (isset($this->columnsDataTypes[$name])) {
					throw Exceptions\ResultException::columnNameIsAlreadyInUse($name);
				}

				if ($this->dataTypesCache === null) {
					$type = \pg_field_type($this->queryResource, $i);
				} else {
					$typeOid = \pg_field_type_oid($this->queryResource, $i);
					if (!isset($this->dataTypesCache[$typeOid])) {
						throw Exceptions\ResultException::noOidInDataTypeCache($typeOid);
					}

					$type = $this->dataTypesCache[$typeOid];
				}

				$this->columnsDataTypes[$name] = $type;
			}
		}

		return $this->columnsDataTypes;
	}

}
