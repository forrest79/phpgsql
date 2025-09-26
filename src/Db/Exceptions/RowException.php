<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class RowException extends Exception
{
	public const int NO_COLUMN = 1;
	public const int NOT_STRING_KEY = 2;
	public const int COLUMN_VALUE_PARSER_MISSING = 3;


	public static function noColumn(string $column): self
	{
		return new self(\sprintf('There is no column \'%s\'.', $column), self::NO_COLUMN);
	}


	public static function notStringKey(): self
	{
		return new self('Requested key must be string.', self::NOT_STRING_KEY);
	}


	public static function columnValueParserMissing(): self
	{
		return new self('You must pass the ColumnValueParser, if there are some rawValues.', self::COLUMN_VALUE_PARSER_MISSING);
	}

}
