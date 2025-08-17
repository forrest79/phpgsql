<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Tests;
use Tester;

require_once __DIR__ . '/../TestCase.php';

/**
 * @testCase
 */
final class ParseDataTypeTest extends Tests\TestCase
{

	public function testParseNull(): void
	{
		Tester\Assert::null($this->createBasicDataTypeParser()->parse('int2', null));
	}


	public function testParseBasic(): void
	{
		$basicDataTypeParser = $this->createBasicDataTypeParser();

		Tester\Assert::same(1, $basicDataTypeParser->parse('int2', '1'));
		Tester\Assert::same(2, $basicDataTypeParser->parse('int4', '2'));
		Tester\Assert::same(3, $basicDataTypeParser->parse('int8', '3'));
		Tester\Assert::same(4, $basicDataTypeParser->parse('oid', '4'));
		Tester\Assert::same(1.1, $basicDataTypeParser->parse('float4', '1.1'));
		Tester\Assert::same(2.2, $basicDataTypeParser->parse('float8', '2.2'));
		Tester\Assert::same(3.3, $basicDataTypeParser->parse('numeric', '3.3'));
		Tester\Assert::same(true, $basicDataTypeParser->parse('bool', 't'));
		Tester\Assert::same(false, $basicDataTypeParser->parse('bool', 'f'));
		Tester\Assert::same('2018-01-01', $basicDataTypeParser->parse('date', '2018-01-01')->format('Y-m-d'));
		Tester\Assert::same('2018-01-01 20:30:00', $basicDataTypeParser->parse('timestamp', '2018-01-01 20:30:00')->format('Y-m-d H:i:s'));
		Tester\Assert::same('2018-01-01 20:30:00.123000', $basicDataTypeParser->parse('timestamp', '2018-01-01 20:30:00.123')->format('Y-m-d H:i:s.u'));
		Tester\Assert::same('2018-01-01 20:30:00+02:00', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00+02')->format('Y-m-d H:i:sP'));
		Tester\Assert::same('2018-01-01 20:30:00.123000+02:00', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00.123+02')->format('Y-m-d H:i:s.uP'));
		Tester\Assert::same('2018-01-01 20:30:00+02:00', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00+02')->format('Y-m-d H:i:sP'));
		Tester\Assert::same('2018-01-01 20:30:00.123000+02:00', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00.123+02')->format('Y-m-d H:i:s.uP'));
		Tester\Assert::same('2018-01-01 20:30:00+02:30', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00+02:30')->format('Y-m-d H:i:sP'));
		Tester\Assert::same('2018-01-01 20:30:00.123000+02:30', $basicDataTypeParser->parse('timestamptz', '2018-01-01 20:30:00.123+02:30')->format('Y-m-d H:i:s.uP'));
		Tester\Assert::same(['key' => 'value'], $basicDataTypeParser->parse('json', '{"key":"value"}'));
		Tester\Assert::same(['column' => 'value'], $basicDataTypeParser->parse('jsonb', '{"column":"value"}'));
		Tester\Assert::same('20:30:00', $basicDataTypeParser->parse('time', '20:30:00'));
		Tester\Assert::same('20:30:00+02', $basicDataTypeParser->parse('timetz', '20:30:00+02'));
		Tester\Assert::same('text1', $basicDataTypeParser->parse('bpchar', 'text1'));
		Tester\Assert::same('text2', $basicDataTypeParser->parse('varchar', 'text2'));
		Tester\Assert::same('text3', $basicDataTypeParser->parse('text', 'text3'));
		Tester\Assert::same('query', $basicDataTypeParser->parse('tsquery', 'query'));
		Tester\Assert::same('vector', $basicDataTypeParser->parse('tsvector', 'vector'));
		Tester\Assert::same('1 day', $basicDataTypeParser->parse('interval', '1 day'));
	}


	public function testParseArrays(): void
	{
		$basicDataTypeParser = $this->createBasicDataTypeParser();

		Tester\Assert::same([1], $basicDataTypeParser->parse('_int2', '{1}'));
		Tester\Assert::same([2], $basicDataTypeParser->parse('_int4', '{2}'));
		Tester\Assert::same([3], $basicDataTypeParser->parse('_int8', '{3}'));
		Tester\Assert::same([4], $basicDataTypeParser->parse('_oid', '{4}'));
		Tester\Assert::same([1.1], $basicDataTypeParser->parse('_float4', '{1.1}'));
		Tester\Assert::same([2.2], $basicDataTypeParser->parse('_float8', '{2.2}'));
		Tester\Assert::same([3.3], $basicDataTypeParser->parse('_numeric', '{3.3}'));
		Tester\Assert::same([true, false], $basicDataTypeParser->parse('_bool', '{t,f}'));
		Tester\Assert::same('2018-01-01', $basicDataTypeParser->parse('_date', '{2018-01-01}')[0]->format('Y-m-d'));
		Tester\Assert::same('2018-01-01 20:30:00', $basicDataTypeParser->parse('_timestamp', '{2018-01-01 20:30:00}')[0]->format('Y-m-d H:i:s'));
		Tester\Assert::same('2018-01-01 20:30:00.123000', $basicDataTypeParser->parse('_timestamp', '{2018-01-01 20:30:00.123}')[0]->format('Y-m-d H:i:s.u'));
		Tester\Assert::same('2018-01-01 20:30:00 +0200', $basicDataTypeParser->parse('_timestamptz', '{2018-01-01 20:30:00+02}')[0]->format('Y-m-d H:i:s O'));
		Tester\Assert::same('2018-01-01 20:30:00.123000 +0200', $basicDataTypeParser->parse('_timestamptz', '{2018-01-01 20:30:00.123+02}')[0]->format('Y-m-d H:i:s.u O'));
		Tester\Assert::same(['20:30:00'], $basicDataTypeParser->parse('_time', '{20:30:00}'));
		Tester\Assert::same(['20:30:00+02'], $basicDataTypeParser->parse('_timetz', '{20:30:00+02}'));
	}


	public function testParseArraysWithNull(): void
	{
		$basicDataTypeParser = $this->createBasicDataTypeParser();

		Tester\Assert::same([1, null, 2], $basicDataTypeParser->parse('_int2', '{1,NULL,2}'));
		Tester\Assert::same([2, null, 3], $basicDataTypeParser->parse('_int4', '{2,NULL,3}'));
		Tester\Assert::same([3, null, 4], $basicDataTypeParser->parse('_int8', '{3,NULL,4}'));
		Tester\Assert::same([4, null, 5], $basicDataTypeParser->parse('_oid', '{4,NULL,5}'));
		Tester\Assert::same([1.1, null, 2.2], $basicDataTypeParser->parse('_float4', '{1.1,NULL,2.2}'));
		Tester\Assert::same([2.2, null, 3.3], $basicDataTypeParser->parse('_float8', '{2.2,NULL,3.3}'));
		Tester\Assert::same([3.3, null, 4.4], $basicDataTypeParser->parse('_numeric', '{3.3,NULL,4.4}'));
		Tester\Assert::same([true, null, false], $basicDataTypeParser->parse('_bool', '{t,NULL,f}'));

		$date = $basicDataTypeParser->parse('_date', '{NULL,2018-01-01}');
		Tester\Assert::null($date[0]);
		Tester\Assert::same('2018-01-01', $date[1]->format('Y-m-d'));

		$timestamp = $basicDataTypeParser->parse('_timestamp', '{NULL,2018-01-01 20:30:00}');
		Tester\Assert::null($timestamp[0]);
		Tester\Assert::same('2018-01-01 20:30:00', $timestamp[1]->format('Y-m-d H:i:s'));

		$timestampTz = $basicDataTypeParser->parse('_timestamptz', '{NULL,2018-01-01 20:30:00+02}');
		Tester\Assert::null($timestampTz[0]);
		Tester\Assert::same('2018-01-01 20:30:00 +0200', $timestampTz[1]->format('Y-m-d H:i:s O'));

		Tester\Assert::same([null, '20:30:00'], $basicDataTypeParser->parse('_time', '{NULL,20:30:00}'));
		Tester\Assert::same([null, '20:30:00+02'], $basicDataTypeParser->parse('_timetz', '{NULL,20:30:00+02}'));
	}


	public function testParseBlankArrays(): void
	{
		Tester\Assert::same([], $this->createBasicDataTypeParser()->parse('_int4', '{}'));
	}


	public function testParseHstore(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('hstore', 'a=>1');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::TRY_USE_CONVERT_TO_JSON);
	}


	public function testParseNonSupportedType(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('point', '(1,2)');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::CANT_PARSE_TYPE);
	}


	public function testArrayConvertToJson(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('_tsvector', '{\'text\'}');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::TRY_USE_CONVERT_TO_JSON);
	}


	public function testArrayNonSupportedType(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('_money', '{1)}');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::CANT_PARSE_TYPE);
	}


	public function testCustomDataTypeParser(): void
	{
		$dataTypeParser = new class implements Db\DataTypeParser {

			public function parse(string $type, string|null $value): mixed
			{
				if (($type === 'point') && ($value !== null)) {
					return \array_map('intval', \explode(',', \substr($value, 1, -1), 2));
				}

				return $value;
			}

		};

		Tester\Assert::same([1, 2], $dataTypeParser->parse('point', '(1,2)'));
	}


	public function testParseBadArray(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('_int2', '123');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::VALUE_IS_NOT_ARRAY);
	}


	public function testParseBadDateTime(): void
	{
		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('date', '20201-02-31');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::CANT_CONVERT_DATETIME);

		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('timestamp', '20201-02-31 12:30:00');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::CANT_CONVERT_DATETIME);

		Tester\Assert::exception(function (): void {
			$this->createBasicDataTypeParser()->parse('timestamptz', '20201-02-31 12:30:00+02');
		}, Db\Exceptions\DataTypeParserException::class, null, Db\Exceptions\DataTypeParserException::CANT_CONVERT_DATETIME);
	}


	private function createBasicDataTypeParser(): Db\DataTypeParsers\Basic
	{
		return new Db\DataTypeParsers\Basic();
	}

}

(new ParseDataTypeTest())->run();
