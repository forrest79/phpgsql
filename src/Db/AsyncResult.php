<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncResult extends Result
{

	public function __construct(RowFactory $rowFactory, DataTypeParsers\DataTypeParser $dataTypeParser)
	{
		parent::__construct(NULL, $rowFactory, $dataTypeParser);
	}


	public function isFinished(): bool
	{
		return $this->queryResource !== NULL;
	}


	/**
	 * @internal
	 * @param resource $queryResource
	 * @return void
	 */
	public function finishAsyncQuery($queryResource): void
	{
		$this->queryResource = $queryResource;
	}

}
