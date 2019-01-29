<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncResult extends Result
{

	public function __construct(RowFactory $rowFactory, DataTypeParser $dataTypeParser, ?array $dataTypesCache)
	{
		parent::__construct(NULL, $rowFactory, $dataTypeParser, $dataTypesCache);
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
