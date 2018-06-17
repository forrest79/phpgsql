<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class DataTypeParserException extends Exception
{
	const CANT_PARSE_TYPE = 1;
	const TRY_USE_CONVERT_TO_JSON = 2;


	public static function cantParseType(string $type, string $value): self
	{
		return new self(\sprintf('Can\'t parse type \'%s\' for value \'%s\'.', $type, $value), self::CANT_PARSE_TYPE);
	}


	public static function tryUseConvertToJson(string $type, string $value, string $pgJsonFunction): self
	{
		return new self(\sprintf('Can\'t parse type \'%s\' for value \'%s\', try convert it JSON with \'%s\'.', $type, $value, $pgJsonFunction), self::TRY_USE_CONVERT_TO_JSON);
	}

}
