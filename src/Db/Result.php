<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \IteratorAggregate<int, Row>
 */
class Result implements \Countable, \IteratorAggregate
{
	/** @var resource */
	protected $queryResource;

	/** @var Query */
	private $query;

	/** @var RowFactory */
	private $rowFactory;

	/** @var DataTypeParser */
	private $dataTypeParser;

	/** @var array<int, string>|NULL */
	private $dataTypesCache;

	/** @var int */
	private $affectedRows;

	/** @var array<string, string> */
	private $columnsDataTypes;


	/**
	 * @param resource $queryResource
	 * @param array<int, string>|NULL $dataTypesCache
	 */
	public function __construct(
		$queryResource,
		Query $query,
		RowFactory $rowFactory,
		DataTypeParser $dataTypeParser,
		?array $dataTypesCache
	)
	{
		$this->queryResource = $queryResource;
		$this->query = $query;
		$this->rowFactory = $rowFactory;
		$this->dataTypeParser = $dataTypeParser;
		$this->dataTypesCache = $dataTypesCache;
	}


	public function setRowFactory(RowFactory $rowFactory): self
	{
		$this->rowFactory = $rowFactory;
		return $this;
	}


	public function fetch(): ?Row
	{
		$data = \pg_fetch_assoc($this->queryResource);
		if ($data === FALSE) {
			return NULL;
		}

		return $this->rowFactory->createRow($this, $data);
	}


	public function getIterator(): ResultIterator
	{
		return new ResultIterator($this);
	}


	public function free(): bool
	{
		return \pg_free_result($this->queryResource);
	}


	/**
	 * @return resource
	 */
	public function getResource()
	{
		return $this->queryResource;
	}


	public function seek(int $row): bool
	{
		return \pg_result_seek($this->queryResource, $row);
	}


	public function count(): int
	{
		return $this->getRowCount();
	}


	public function getRowCount(): int
	{
		return \pg_num_rows($this->queryResource);
	}


	/**
	 * Like fetch(), but returns only first field.
	 *
	 * @return mixed value on success, NULL if no next record
	 */
	public function fetchSingle()
	{
		$row = $this->fetch();
		if ($row === NULL) {
			return NULL;
		}
		$columns = $this->getColumns();
		return $row[\reset($columns)];
	}


	/**
	 * Fetches all records from table.
	 *
	 * @return array<Row>
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		$limit = $limit ?? -1;
		$this->seek($offset ?? 0);
		$row = $this->fetch();
		if ($row === NULL) {
			return [];  // empty result set
		}

		$data = [];
		do {
			if ($limit === 0) {
				break;
			}
			$limit--;
			$data[] = $row;

			$row = $this->fetch();
		} while ($row !== NULL);

		return $data;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * Examples:
	 * - associative descriptor: col1[]col2
	 *   builds a tree:          $tree[$val1][$index][$val2] = {record}
	 * - associative descriptor: col1|col2=col3
	 *   builds a tree:          $tree[$val1][$val2] = val2
	 *
	 * @return array<int|string, Row|array|mixed>
	 * @throws Exceptions\ResultException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchAssoc(string $assocDesc): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if ($row === NULL) {
			return [];  // empty result set
		}

		$data = [];
		$assoc = \preg_split('#(\[\]|=|\|)#', $assocDesc, -1, \PREG_SPLIT_DELIM_CAPTURE | \PREG_SPLIT_NO_EMPTY);
		if ($assoc === FALSE) {
			throw Exceptions\ResultException::fetchAssocParseFailed($assocDesc);
		}

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '[]' && $as !== '=' && $as !== '|' && $row->hasColumn($as) === FALSE) {
				throw Exceptions\ResultException::noColumn($as);
			}
		}

		if ($assoc === []) {
			$assoc[] = '[]';
		}

		// make associative tree
		do {
			$x = &$data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '[]') { // indexed-array node
					$x = &$x[];
				} else if ($as === '=') { // "value" node
					$x = $row->{$assoc[$i + 1]};
					$row = $this->fetch();
					continue 2;
				} else if ($as !== '|') { // associative-array node
					$x = &$x[$row->$as];
				}
			}

			if ($x === NULL) { // build leaf
				$x = $row;
			}

			$row = $this->fetch();
		} while ($row !== NULL);

		unset($x);
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 *
	 * @return array<mixed, mixed>
	 * @throws Exceptions\ResultException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if ($row === NULL) {
			return [];  // empty result set
		}

		$data = [];

		if ($value === NULL) {
			if ($key !== NULL) {
				throw Exceptions\ResultException::fetchPairsBadColumns();
			}

			// autodetect
			$tmp = \array_keys($row->toArray());
			$key = $tmp[0];
			if (\count($row) < 2) { // indexed-array
				do {
					$data[] = $row[$key];
					$row = $this->fetch();
				} while ($row !== NULL);
				return $data;
			}

			$value = $tmp[1];

		} else {
			if ($row->hasColumn($value) === FALSE) {
				throw Exceptions\ResultException::noColumn($value);
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
					$row = $this->fetch();
				} while ($row !== NULL);
				return $data;
			}

			if ($row->hasColumn($key) === FALSE) {
				throw Exceptions\ResultException::noColumn($key);
			}
		}

		do {
			$data[(string) $row[$key]] = $row[$value];
			$row = $this->fetch();
		} while ($row !== NULL);

		return $data;
	}


	public function getQuery(): Query
	{
		return $this->query;
	}


	public function getAffectedRows(): int
	{
		if ($this->affectedRows === NULL) {
			$this->affectedRows = \pg_affected_rows($this->queryResource);
		}

		return $this->affectedRows;
	}


	/**
	 * @throws Exceptions\ResultException
	 */
	public function getColumnType(string $column): string
	{
		$type = $this->getColumnsDataTypes()[$column] ?? NULL;
		if ($type === NULL) {
			throw Exceptions\ResultException::noColumn($column);
		}
		return $type;
	}


	/**
	 * @return array<string>
	 */
	public function getColumns(): array
	{
		return \array_keys($this->getColumnsDataTypes());
	}


	/**
	 * @param mixed $rawValue
	 * @return mixed
	 */
	public function parseColumnValue(string $column, $rawValue)
	{
		return $this->dataTypeParser->parse($this->getColumnType($column), $rawValue);
	}


	/**
	 * @return array<string, string>
	 */
	private function getColumnsDataTypes(): array
	{
		if ($this->columnsDataTypes === NULL) {
			$this->columnsDataTypes = [];
			$fieldsCnt = \pg_num_fields($this->queryResource);
			for ($i = 0; $i < $fieldsCnt; $i++) {
				$name = \pg_field_name($this->queryResource, $i);
				if (isset($this->columnsDataTypes[$name])) {
					throw Exceptions\ResultException::columnNameIsAlreadyInUse($name);
				}
				if ($this->dataTypesCache === NULL) {
					$type = \pg_field_type($this->queryResource, $i);
				} else {
					$type = $this->dataTypesCache[\pg_field_type_oid($this->queryResource, $i)];
				}
				$this->columnsDataTypes[$name] = $type;
			}
		}
		return $this->columnsDataTypes;
	}

}
