<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \Iterator<int, Row>
 */
class RowIterator implements \Iterator
{
	private Result $result;

	private Row|NULL $row = NULL;

	private int $pointer;


	public function __construct(Result $result)
	{
		$this->result = $result;
	}


	public function rewind(): void
	{
		$this->pointer = 0;
		$this->result->seek(0);
		$this->row = $this->result->fetch();
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
		$this->row = $this->result->fetch();
		$this->pointer++;
	}


	public function valid(): bool
	{
		return $this->row !== NULL;
	}

}
