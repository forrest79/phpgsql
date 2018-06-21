<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class ResultIterator implements \Iterator
{
	/** @var Result */
	private $result;

	/** @var Row */
	private $row;

	/** @var int */
	private $pointer;


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
		return !empty($this->row);
	}

}
