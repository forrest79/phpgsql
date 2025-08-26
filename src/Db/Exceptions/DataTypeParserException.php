<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class DataTypeParserException extends Exception
{
	public const int CANT_PARSE_TYPE = 1;
	public const int VALUE_IS_NOT_ARRAY = 2;
	public const int CANT_CONVERT_DATETIME = 3;
	public const int TRY_USE_CONVERT_TO_JSON = 4;


	public static function cantParseType(string $type, string $value): self
	{
		return new self(\sprintf('Can\'t parse type \'%s\' for value \'%s\'.', $type, $value), self::CANT_PARSE_TYPE);
	}


	public static function valueIsNotArray(string $value): self
	{
		return new self(\sprintf('Value \'%s\' isn\'t an array.', $value), self::VALUE_IS_NOT_ARRAY);
	}


	public static function cantConvertDatetime(string $format, string $value): self
	{
		return new self(\sprintf('Can\'t convert value \'%s\' to datetime with format \'%s\'.', $value, $format), self::CANT_CONVERT_DATETIME);
	}


	public static function tryUseConvertToJson(string $type, string $value, string $pgJsonFunction): self
	{
		return new self(\sprintf('Can\'t parse type \'%s\' for value \'%s\', try convert it JSON with \'%s\'.', $type, $value, $pgJsonFunction), self::TRY_USE_CONVERT_TO_JSON);
	}

}
