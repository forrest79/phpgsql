<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class ResultBuilder
{
	private Connection $connection;

	private Events $events;

	private ResultFactory|null $resultFactory = null;

	private RowFactory|null $rowFactory = null;

	private DataTypeParser|null $dataTypeParser = null;

	private DataTypeCache|null $dataTypeCache = null;


	public function __construct(Connection $connection, Events $events)
	{
		$this->connection = $connection;
		$this->events = $events;
	}


	public function build(PgSql\Result $resource, Query $query): Result
	{
		$result = $this->getResultFactory()->create(
			$resource,
			$query,
			$this->getRowFactory(),
			$this->getDataTypeParser(),
			$this->getDataTypesCache(),
		);

		$this->events->onResult($result);

		return $result;
	}


	public function setResultFactory(ResultFactory $resultFactory): static
	{
		$this->resultFactory = $resultFactory;

		return $this;
	}


	private function getResultFactory(): ResultFactory
	{
		if ($this->resultFactory === null) {
			$this->resultFactory = new ResultFactories\Basic();
		}

		return $this->resultFactory;
	}


	public function setRowFactory(RowFactory $rowFactory): void
	{
		$this->rowFactory = $rowFactory;
	}


	private function getRowFactory(): RowFactory
	{
		if ($this->rowFactory === null) {
			$this->rowFactory = new RowFactories\Basic();
		}

		return $this->rowFactory;
	}


	public function setDataTypeParser(DataTypeParser $dataTypeParser): void
	{
		$this->dataTypeParser = $dataTypeParser;
	}


	private function getDataTypeParser(): DataTypeParser
	{
		if ($this->dataTypeParser === null) {
			$this->dataTypeParser = new DataTypeParsers\Basic();
		}

		return $this->dataTypeParser;
	}


	public function setDataTypeCache(DataTypeCache $dataTypeCache): void
	{
		$this->dataTypeCache = $dataTypeCache;
	}


	/**
	 * @return array<int, string>|null
	 */
	private function getDataTypesCache(): array|null
	{
		return $this->dataTypeCache?->load($this->connection) ?? null;
	}

}
