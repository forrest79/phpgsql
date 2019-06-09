<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Result implements \Countable, \IteratorAggregate
{
	/** @var resource|NULL */
	protected $queryResource;

	/** @var RowFactory */
	private $rowFactory;

	/** @var DataTypeParser */
	private $dataTypeParser;

	/** @var array|NULL */
	private $dataTypesCache;

	/** @var int */
	private $affectedRows;

	/** @var array */
	private $columnsDataTypes;


	/**
	 * @param resource|NULL $queryResource
	 * @param RowFactory $rowFactory
	 * @param DataTypeParser $dataTypeParser
	 * @param array|NULL $dataTypesCache
	 */
	public function __construct(
		$queryResource,
		RowFactory $rowFactory,
		DataTypeParser $dataTypeParser,
		?array $dataTypesCache
	)
	{
		$this->queryResource = $queryResource;
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
		$data = \pg_fetch_assoc($this->getResource());
		if ($data === FALSE) {
			return NULL;
		}

		return $this->rowFactory->createRow($data, $this->getColumnsDataTypes(), $this->dataTypeParser);
	}


	public function getIterator(): ResultIterator
	{
		return new ResultIterator($this);
	}


	public function free(): bool
	{
		return \pg_free_result($this->getResource());
	}


	/**
	 * @return resource
	 */
	public function getResource()
	{
		if ($this->queryResource === NULL) {
			throw Exceptions\ResultException::noResource();
		}
		return $this->queryResource;
	}


	public function seek(int $row): bool
	{
		return \pg_result_seek($this->getResource(), $row);
	}


	public function count(): int
	{
		return $this->getRowCount();
	}


	public function getRowCount(): int
	{
		return \pg_num_rows($this->getResource());
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
	 * @return Row[]
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		$limit = $limit ?? -1;
		$this->seek($offset ?: 0);
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
		$assoc = \preg_split('#(\[\]|=|\|)#', $assocDesc, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
		if ($assoc === FALSE) {
			throw Exceptions\ResultException::fetchAssocParseFailed($assocDesc);
		}

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '[]' && $as !== '=' && $as !== '|' && $row->hasKey($as) === FALSE) {
				throw Exceptions\ResultException::noColumn($as);
			}
		}

		if (\count($assoc) === 0) {
			$assoc[] = '[]';
		}

		// make associative tree
		do {
			$x = & $data;

			// iterative deepening
			foreach ($assoc as $i => $as) {
				if ($as === '[]') { // indexed-array node
					$x = & $x[];
				} else if ($as === '=') { // "value" node
					$x = $row->{$assoc[$i + 1]};
					$row = $this->fetch();
					continue 2;
				} else if ($as !== '|') { // associative-array node
					$x = & $x[$row->$as];
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
			if ($row->hasKey($value) === FALSE) {
				throw Exceptions\ResultException::noColumn($value);
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
					$row = $this->fetch();
				} while ($row !== NULL);
				return $data;
			}

			if ($row->hasKey($key) === FALSE) {
				throw Exceptions\ResultException::noColumn($key);
			}
		}

		do {
			$data[(string) $row[$key]] = $row[$value];
			$row = $this->fetch();
		} while ($row !== NULL);

		return $data;
	}


	/**
	 * @throws Exceptions\ResultException
	 */
	public function getColumnType(string $key): string
	{
		if (!isset($this->getColumnsDataTypes()[$key])) {
			throw Exceptions\ResultException::noColumn($key);
		}
		return $this->getColumnsDataTypes()[$key];
	}


	public function getColumns(): array
	{
		return \array_keys($this->getColumnsDataTypes());
	}


	public function getAffectedRows(): int
	{
		if ($this->affectedRows === NULL) {
			$this->affectedRows = \pg_affected_rows($this->getResource());
		}

		return $this->affectedRows;
	}


	private function getColumnsDataTypes(): array
	{
		if ($this->columnsDataTypes === NULL) {
			$queryResource = $this->getResource();
			$this->columnsDataTypes = [];
			for ($i = 0; $i < \pg_num_fields($queryResource); $i++) {
				$name = \pg_field_name($queryResource, $i);
				$type = $this->dataTypesCache === NULL
					? \pg_field_type($queryResource, $i)
					: $this->dataTypesCache[\pg_field_type_oid($queryResource, $i)];
				$this->columnsDataTypes[$name] = $type;
			}
		}
		return $this->columnsDataTypes;
	}

}
