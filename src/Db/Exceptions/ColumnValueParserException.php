<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ColumnValueParserException extends Exception
{
	public const int NO_COLUMN = 1;


	public static function noColumn(string $column): self
	{
		return new self(\sprintf('There is no column \'%s\'.', $column), self::NO_COLUMN);
	}

}
