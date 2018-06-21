<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db\Exceptions;

class Result implements \Countable, \IteratorAggregate
{
	protected $queryResource;

	/** @var RowFactory */
	private $rowFactory;

	/** @var DataTypeParsers\DataTypeParser */
	private $dataTypeParser;

	/** @var int */
	private $affectedRows;

	/** @var array */
	private $columnsDataTypes;


	public function __construct($queryResource, RowFactory $rowFactory, DataTypeParsers\DataTypeParser $dataTypeParser)
	{
		$this->queryResource = $queryResource;
		$this->rowFactory = $rowFactory;
		$this->dataTypeParser = $dataTypeParser;
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


	public function getResource()
	{
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
	 * @return mixed value on success, null if no next record
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
	 * @return Row[]
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		$limit = $limit === NULL ? -1 : $limit;
		$this->seek($offset ?: 0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];
		do {
			if ($limit === 0) {
				break;
			}
			$limit--;
			$data[] = $row;
		} while ($row = $this->fetch());

		return $data;
	}


	/**
	 * Fetches all records from table and returns associative tree.
	 * Examples:
	 * - associative descriptor: col1[]col2
	 *   builds a tree:          $tree[$val1][$index][$val2] = {record}
	 * - associative descriptor: col1|col2=col3
	 *   builds a tree:          $tree[$val1][$val2] = val2
	 * @throws \InvalidArgumentException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchAssoc(string $assoc): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = NULL;
		$assoc = \preg_split('#(\[\]|=|\|)#', $assoc, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		// check columns
		foreach ($assoc as $as) {
			// offsetExists ignores NULL in PHP 5.2.1, isset() surprisingly NULL accepts
			if ($as !== '[]' && $as !== '=' && $as !== '|' && !$row->hasKey($as)) {
				throw new \InvalidArgumentException("Unknown column '$as' in associative descriptor.");
			}
		}

		if (empty($assoc)) {
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
					continue 2;
				} else if ($as !== '|') { // associative-array node
					$x = & $x[$row->$as];
				}
			}

			if ($x === NULL) { // build leaf
				$x = $row;
			}
		} while ($row = $this->fetch());

		unset($x);
		return $data;
	}


	/**
	 * Fetches all records from table like $key => $value pairs.
	 * @throws \InvalidArgumentException
	 * @credit dibi (https://dibiphp.com/) | David Grudl
	 */
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		$this->seek(0);
		$row = $this->fetch();
		if (!$row) {
			return [];  // empty result set
		}

		$data = [];

		if ($value === NULL) {
			if ($key !== NULL) {
				throw new \InvalidArgumentException('Either none or both columns must be specified.');
			}

			// autodetect
			$tmp = \array_keys($row->toArray());
			$key = $tmp[0];
			if (count($row) < 2) { // indexed-array
				do {
					$data[] = $row[$key];
				} while ($row = $this->fetch());
				return $data;
			}

			$value = $tmp[1];

		} else {
			if (!$row->hasKey($value)) {
				throw new \InvalidArgumentException("Unknown value column '$value'.");
			}

			if ($key === NULL) { // indexed-array
				do {
					$data[] = $row[$value];
				} while ($row = $this->fetch());
				return $data;
			}

			if (!$row->hasKey($key)) {
				throw new \InvalidArgumentException("Unknown key column '$key'.");
			}
		}

		do {
			$data[(string) $row[$key]] = $row[$value];
		} while ($row = $this->fetch());

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


	private function getColumnsDataTypes()
	{
		if ($this->columnsDataTypes === NULL) {
			$queryResource = $this->getResource();
			$this->columnsDataTypes = [];
			for ($i = 0; $i < \pg_num_fields($queryResource); $i++) {
				$name = \pg_field_name($queryResource, $i);
				$type = \pg_field_type($queryResource, $i);
				$this->columnsDataTypes[$name] = $type;
			};
		}
		return $this->columnsDataTypes;
	}

}
