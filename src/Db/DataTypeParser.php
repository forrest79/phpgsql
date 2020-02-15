<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface DataTypeParser
{

	/**
	 * @return mixed
	 */
	function parse(string $type, ?string $value);

}
