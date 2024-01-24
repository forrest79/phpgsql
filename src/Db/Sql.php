<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface Sql
{

	function getSql(): string;


	/**
	 * @return list<mixed>
	 */
	function getParams(): array;

}
