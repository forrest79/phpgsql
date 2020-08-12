<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \Iterator<int, Row>
 */
class ResultIterator implements \Iterator, \Countable
{
	/** @var Result */
	private $result;

	/** @var Row|NULL */
	private $row;

	/** @var int */
	private $pointer;

	/** @var array<int, Row|NULL> */
	private $data = [];


	public function __construct(Result $result)
	{
		$this->result = $result;
	}


	public function rewind(): void
	{
		$this->pointer = 0;
		$this->result->seek(0);
		\reset($this->data);
	}


	public function key(): int
	{
		return $this->pointer;
	}


	public function current(): Row
	{
		return $this->row;
	}


	public function next(): void
	{
		$this->pointer++;
	}


	public function valid(): bool
	{
		$this->row = $this->fetch();
		return $this->row !== NULL;
	}


	public function count(): int
	{
		return $this->result->getRowCount();
	}


	private function fetch(): ?Row
	{
		if (!\array_key_exists($this->pointer, $this->data)) {
			$this->data[$this->pointer] = $this->result->fetch();
		}
		return $this->data[$this->pointer];
	}

}
