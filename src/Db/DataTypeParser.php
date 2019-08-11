<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface DataTypeParser
{

	/**
	 * @param string $type
	 * @param string|NULL $value
	 * @return mixed
	 */
	function parse(string $type, ?string $value);

}
