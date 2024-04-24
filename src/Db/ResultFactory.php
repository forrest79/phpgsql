<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

interface ResultFactory
{

	function createResult(PgSql\Result $resource, Query $query): Result;

}
