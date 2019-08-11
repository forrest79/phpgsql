<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface Queryable
{

	function getSql(): string;

	function getParams(): array;

}
