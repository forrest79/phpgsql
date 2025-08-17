<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeParsers;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Db\Exceptions;

class Basic implements Db\DataTypeParser
{

	/**
	 * @throws Exceptions\DataTypeParserException
	 */
	public function parse(string $type, string|null $value): mixed
	{
		if ($value === null) {
			return null;
		}

		if ($type[0] === '_') { // arrays
			switch ($type) {
				case '_int2':
				case '_int4':
				case '_int8':
				case '_oid':
					return $this->parseArray($value, 'intval');
				case '_float4':
				case '_float8':
				case '_numeric':
					return $this->parseArray($value, 'floatval');
				case '_bool':
					return $this->parseArray($value, [$this, 'parseBool']);
				case '_date':
					return $this->parseArray($value, [$this, 'parseDate']);
				case '_timestamp':
					return $this->parseArray($value, function (string $value): \DateTimeImmutable {
						return $this->parseTimestamp(\trim($value, '"'));
					});
				case '_timestamptz':
					return $this->parseArray($value, function (string $value): \DateTimeImmutable {
						return $this->parseTimestampTz(\trim($value, '"'));
					});
				case '_time':
				case '_timetz':
					return $this->parseArray($value);
				case '_bpchar':
				case '_varchar':
				case '_text':
				case '_tsquery':
				case '_tsvector':
				case '_interval':
					throw Exceptions\DataTypeParserException::tryUseConvertToJson($type, $value, 'array_to_json');
				default:
					throw Exceptions\DataTypeParserException::cantParseType($type, $value);
			}
		} else {
			switch ($type) {
				case 'int2':
				case 'int4':
				case 'int8':
				case 'oid':
					return (int) $value;
				case 'float4':
				case 'float8':
				case 'numeric':
					return (float) $value;
				case 'bool':
					return $this->parseBool($value);
				case 'date':
					return $this->parseDate($value);
				case 'timestamp':
					return $this->parseTimestamp($value);
				case 'timestamptz':
					return $this->parseTimestampTz($value);
				case 'json':
				case 'jsonb':
					return \json_decode($value, true);
				case 'time':
				case 'timetz':
				case 'bpchar':
				case 'varchar':
				case 'text':
				case 'tsquery':
				case 'tsvector':
				case 'interval':
					return $value;
				case 'hstore':
					throw Exceptions\DataTypeParserException::tryUseConvertToJson($type, $value, 'hstore_to_json');
				default:
					throw Exceptions\DataTypeParserException::cantParseType($type, $value);
			}
		}
	}


	/**
	 * @return list<mixed>
	 */
	protected function parseArray(string $value, callable|null $typeFnc = null): array
	{
		if ((\substr($value, 0, 1) !== '{') || (\substr($value, -1) !== '}')) {
			throw Exceptions\DataTypeParserException::valueIsNotArray($value);
		}

		$value = \substr($value, 1, -1);
		if ($value === '') {
			return [];
		}

		$array = \explode(',', $value);
		foreach ($array as $i => $item) {
			if ($item === 'NULL') {
				$array[$i] = null;
			} else if ($typeFnc !== null) {
				$array[$i] = $typeFnc($item);
			}
		}

		/** @phpstan-var list<mixed> */
		return $array;
	}


	protected function parseBool(string $value): bool
	{
		return $value === 't';
	}


	/**
	 * @throws Exceptions\DataTypeParserException
	 */
	protected function parseDate(string $value): \DateTimeImmutable
	{
		$datetime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value . ' 00:00:00');
		if ($datetime === false) {
			throw Exceptions\DataTypeParserException::cantConvertDatetime('Y-m-d H:i:s', $value . ' 00:00:00');
		}
		return $datetime;
	}


	/**
	 * @throws Exceptions\DataTypeParserException
	 */
	protected function parseTimestamp(string $value): \DateTimeImmutable
	{
		$datetime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value);

		if ($datetime === false) {
			$datetime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.u', $value);
		}

		if ($datetime === false) {
			throw Exceptions\DataTypeParserException::cantConvertDatetime('Y-m-d H:i:s/Y-m-d H:i:s.u', $value);
		}

		return $datetime;
	}


	/**
	 * @throws Exceptions\DataTypeParserException
	 */
	protected function parseTimestampTz(string $value): \DateTimeImmutable
	{
		$datetime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:sP', $value);

		if ($datetime === false) {
			$datetime = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s.uP', $value);
		}

		if ($datetime === false) {
			throw Exceptions\DataTypeParserException::cantConvertDatetime('Y-m-d H:i:sP/Y-m-d H:i:s.uP', $value);
		}

		return $datetime;
	}

}
