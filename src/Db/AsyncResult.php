<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db\Exceptions;

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
	 * @throws Exceptions\ResultException
	 */
	public function getResource()
	{
		if ($this->queryResource === NULL) {
			throw Exceptions\ResultException::noResource();
		}
		return parent::getResource();
	}


	/**
	 * @internal
	 */
	public function finishAsyncQuery($queryResource)
	{
		$this->queryResource = $queryResource;
	}

}
