<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\ResultFactories;

use Forrest79\PhPgSql\Db;
use PgSql;

class Basic implements Db\ResultFactory
{
	private Db\Internals $internal;


	public function __construct(Db\Internals $internal)
	{
		$this->internal = $internal;
	}


	public function createResult(PgSql\Result $resource, Db\Query $query): Db\Result
	{
		return new Db\Result(
			$resource,
			$query,
			$this->internal->getDefaultRowFactory(),
			$this->internal->getDataTypeParser(),
			$this->internal->getDataTypesCache(),
		);
	}

}
