<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Helper
{

	/**
	 * @param array<mixed> $array
	 */
	public static function createStringPgArray(array $array): string
	{
		if ($array === []) {
			return '{}';
		}
		foreach ($array as $i => $value) {
			if ($value === NULL) {
				$array[$i] = 'NULL';
			} else {
				\assert(\is_scalar($value));
				$array[$i] = '"' . \str_replace('"', '\"', (string) $value) . '"';
			}
		}
		return '{' . \implode(',', $array) . '}';
	}


	/**
	 * @param array<mixed> $array
	 */
	public static function createPgArray(array $array): string
	{
		if ($array === []) {
			return '{}';
		}
		foreach ($array as $i => $value) {
			if ($value === NULL) {
				$array[$i] = 'NULL';
			}
		}
		return '{' . \implode(',', $array) . '}';
	}

}
