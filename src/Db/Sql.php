<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface Sql
{

	function getSql(): string;

	function getParams(): array;

}
