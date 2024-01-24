<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface DataTypeParser
{

	function parse(string $type, string|NULL $value): mixed;

}
