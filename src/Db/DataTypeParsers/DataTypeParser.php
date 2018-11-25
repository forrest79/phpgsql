<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeParsers;

interface DataTypeParser
{

	/**
	 * @param string $type
	 * @param string|NULL $value
	 * @return mixed
	 */
	public function parse(string $type, ?string $value);

}
