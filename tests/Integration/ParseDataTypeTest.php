<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
final class ParseDataTypeTest extends TestCase
{

	public function testParseNull(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_integer integer
			);
		');

		$this->connection->queryArgs('
			INSERT INTO test(type_integer)
			VALUES (?)
		', [NULL]);

		Tester\Assert::null($this->connection->query('SELECT type_integer FROM test')->fetchSingle());
	}


	public function testParseBasic(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type_integer integer,
				type_bigint bigint,
				type_smallint smallint,
				type_oid oid,
				type_numeric numeric,
				type_decimal decimal,
				type_real real,
				type_double double precision,
				type_float float,
				type_bool_true boolean,
				type_bool_false boolean,
				type_date date,
				type_time time,
				type_timetz timetz,
				type_timestamp timestamp,
				type_timestamptz timestamptz,
				type_varchar character varying,
				type_text text,
				type_char char(10),
				type_json json,
				type_jsonb jsonb,
				type_tsquery tsquery,
				type_tsrange tsrange,
				type_tstzrange tstzrange,
				type_tsvector tsvector,
				type_interval interval
			);
		');

		$this->connection->queryArgs('
			INSERT INTO test(
					type_integer,
					type_bigint,
					type_smallint,
					type_oid,
					type_numeric,
					type_decimal,
					type_real,
					type_double,
					type_float,
					type_bool_true,
					type_bool_false,
					type_date,
					type_time,
					type_timetz,
					type_timestamp,
					type_timestamptz,
					type_varchar,
					type_text,
					type_char,
					type_json,
					type_jsonb,
					type_tsquery,
					type_tsvector,
					type_interval
				)
			VALUES (?, ?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		', [
			1,
			2,
			3,
			4,
			1.1,
			2.2,
			3.3,
			4.4,
			5.5,
			TRUE,
			FALSE,
			'2018-01-01',
			'20:30:00',
			'20:30:00+02',
			'2018-01-01 20:30:00',
			'2018-01-01 20:30:00+02',
			'text1',
			'text2',
			'text3',
			'{"key":"value"}',
			'{"column":"value"}',
			'query',
			'vector',
			'1 day',
		]);

		$row = $this->fetch();

		Tester\Assert::true(\is_int($row->id));
		Tester\Assert::same(1, $row->id);

		Tester\Assert::true(\is_int($row->type_integer));
		Tester\Assert::same(1, $row->type_integer);

		Tester\Assert::true(\is_int($row->type_bigint));
		Tester\Assert::same(2, $row->type_bigint);

		Tester\Assert::true(\is_int($row->type_smallint));
		Tester\Assert::same(3, $row->type_smallint);

		Tester\Assert::true(\is_int($row->type_oid));
		Tester\Assert::same(4, $row->type_oid);

		Tester\Assert::true(\is_float($row->type_numeric));
		Tester\Assert::same(1.1, $row->type_numeric);

		Tester\Assert::true(\is_float($row->type_decimal));
		Tester\Assert::same(2.2, $row->type_decimal);

		Tester\Assert::true(\is_float($row->type_real));
		Tester\Assert::same(3.3, $row->type_real);

		Tester\Assert::true(\is_float($row->type_double));
		Tester\Assert::same(4.4, $row->type_double);

		Tester\Assert::true(\is_float($row->type_float));
		Tester\Assert::same(5.5, $row->type_float);

		Tester\Assert::true(\is_bool($row->type_bool_true));
		Tester\Assert::true($row->type_bool_true);

		Tester\Assert::true(\is_bool($row->type_bool_false));
		Tester\Assert::false($row->type_bool_false);

		Tester\Assert::true($row->type_date instanceof \DateTimeImmutable);
		\assert($row->type_date instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01', $row->type_date->format('Y-m-d'));

		Tester\Assert::true(\is_string($row->type_time));
		Tester\Assert::same('20:30:00', $row->type_time);

		Tester\Assert::true(\is_string($row->type_timetz));
		Tester\Assert::same('20:30:00+02', $row->type_timetz);

		Tester\Assert::true($row->type_timestamp instanceof \DateTimeImmutable);
		\assert($row->type_timestamp instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00', $row->type_timestamp->format('Y-m-d H:i:s'));

		Tester\Assert::true($row->type_timestamptz instanceof \DateTimeImmutable);
		\assert($row->type_timestamptz instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00+02:00', $row->type_timestamptz->setTimezone(new \DateTimeZone('+2:00'))->format('Y-m-d H:i:sP'));

		Tester\Assert::true(\is_string($row->type_varchar));
		Tester\Assert::same('text1', $row->type_varchar);

		Tester\Assert::true(\is_string($row->type_text));
		Tester\Assert::same('text2', $row->type_text);

		Tester\Assert::true(\is_string($row->type_char));
		Tester\Assert::same('text3     ', $row->type_char);

		Tester\Assert::true(\is_array($row->type_json));
		Tester\Assert::same(['key' => 'value'], $row->type_json);

		Tester\Assert::true(\is_array($row->type_jsonb));
		Tester\Assert::same(['column' => 'value'], $row->type_jsonb);

		Tester\Assert::true(\is_string($row->type_tsquery));
		Tester\Assert::same('\'query\'', $row->type_tsquery);

		Tester\Assert::true(\is_string($row->type_tsvector));
		Tester\Assert::same('\'vector\'', $row->type_tsvector);

		Tester\Assert::true(\is_string($row->type_interval));
		Tester\Assert::same('1 day', $row->type_interval);
	}


	public function testParseArrays(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_integer integer[],
				type_bigint bigint[],
				type_smallint smallint[],
				type_oid oid[],
				type_numeric numeric[],
				type_decimal decimal[],
				type_real real[],
				type_double double precision[],
				type_float float[],
				type_bool_true boolean[],
				type_bool_false boolean[],
				type_date date[],
				type_time time[],
				type_timetz timetz[],
				type_timestamp timestamp[],
				type_timestamptz timestamptz[]
			);
		');

		$this->connection->queryArgs('
			INSERT INTO test(
					type_integer,
					type_bigint,
					type_smallint,
					type_oid,
					type_numeric,
					type_decimal,
					type_real,
					type_double,
					type_float,
					type_bool_true,
					type_bool_false,
					type_date,
					type_time,
					type_timetz,
					type_timestamp,
					type_timestamptz
				)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		', [
			'{1}',
			'{2}',
			'{3}',
			'{4}',
			'{1.1}',
			'{2.2}',
			'{3.3}',
			'{4.4}',
			'{5.5}',
			'{TRUE}',
			'{FALSE}',
			'{\'2018-01-01\'}',
			'{\'20:30:00\'}',
			'{\'20:30:00+02\'}',
			'{\'2018-01-01 20:30:00\'}',
			'{\'2018-01-01 20:30:00+02\'}',
		]);

		$row = $this->fetch();

		Tester\Assert::true(\is_array($row->type_integer));
		Tester\Assert::true(\is_int($row->type_integer[0]));
		Tester\Assert::same(1, $row->type_integer[0]);

		Tester\Assert::true(\is_array($row->type_bigint));
		Tester\Assert::true(\is_int($row->type_bigint[0]));
		Tester\Assert::same(2, $row->type_bigint[0]);

		Tester\Assert::true(\is_array($row->type_smallint));
		Tester\Assert::true(\is_int($row->type_smallint[0]));
		Tester\Assert::same(3, $row->type_smallint[0]);

		Tester\Assert::true(\is_array($row->type_oid));
		Tester\Assert::true(\is_int($row->type_oid[0]));
		Tester\Assert::same(4, $row->type_oid[0]);

		Tester\Assert::true(\is_array($row->type_numeric));
		Tester\Assert::true(\is_float($row->type_numeric[0]));
		Tester\Assert::same(1.1, $row->type_numeric[0]);

		Tester\Assert::true(\is_array($row->type_decimal));
		Tester\Assert::true(\is_float($row->type_decimal[0]));
		Tester\Assert::same(2.2, $row->type_decimal[0]);

		Tester\Assert::true(\is_array($row->type_real));
		Tester\Assert::true(\is_float($row->type_real[0]));
		Tester\Assert::same(3.3, $row->type_real[0]);

		Tester\Assert::true(\is_array($row->type_double));
		Tester\Assert::true(\is_float($row->type_double[0]));
		Tester\Assert::same(4.4, $row->type_double[0]);

		Tester\Assert::true(\is_array($row->type_float));
		Tester\Assert::true(\is_float($row->type_float[0]));
		Tester\Assert::same(5.5, $row->type_float[0]);

		Tester\Assert::true(\is_array($row->type_bool_true));
		Tester\Assert::true(\is_bool($row->type_bool_true[0]));
		Tester\Assert::true($row->type_bool_true[0]);

		Tester\Assert::true(\is_array($row->type_bool_false));
		Tester\Assert::true(\is_bool($row->type_bool_false[0]));
		Tester\Assert::false($row->type_bool_false[0]);

		Tester\Assert::true(\is_array($row->type_date));
		Tester\Assert::true($row->type_date[0] instanceof \DateTimeImmutable);
		\assert(\is_array($row->type_date));
		\assert($row->type_date[0] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01', $row->type_date[0]->format('Y-m-d'));

		Tester\Assert::true(\is_array($row->type_time));
		Tester\Assert::true(\is_string($row->type_time[0]));
		Tester\Assert::same('20:30:00', $row->type_time[0]);

		Tester\Assert::true(\is_array($row->type_timetz));
		Tester\Assert::true(\is_string($row->type_timetz[0]));
		Tester\Assert::same('20:30:00+02', $row->type_timetz[0]);

		Tester\Assert::true(\is_array($row->type_timestamp));
		Tester\Assert::true($row->type_timestamp[0] instanceof \DateTimeImmutable);
		\assert(\is_array($row->type_timestamp));
		\assert($row->type_timestamp[0] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00', $row->type_timestamp[0]->format('Y-m-d H:i:s'));

		Tester\Assert::true(\is_array($row->type_timestamptz));
		Tester\Assert::true($row->type_timestamptz[0] instanceof \DateTimeImmutable);
		\assert(\is_array($row->type_timestamptz));
		\assert($row->type_timestamptz[0] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00+02:00', $row->type_timestamptz[0]->setTimezone(new \DateTimeZone('+2:00'))->format('Y-m-d H:i:sP'));
	}


	public function testParseArraysWithNull(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_integer integer[],
				type_bigint bigint[],
				type_smallint smallint[],
				type_oid oid[],
				type_numeric numeric[],
				type_decimal decimal[],
				type_real real[],
				type_double double precision[],
				type_float float[],
				type_bool boolean[],
				type_date date[],
				type_time time[],
				type_timetz timetz[],
				type_timestamp timestamp[],
				type_timestamptz timestamptz[]
			);
		');

		$this->connection->queryArgs('
			INSERT INTO test(
					type_integer,
					type_bigint,
					type_smallint,
					type_oid,
					type_numeric,
					type_decimal,
					type_real,
					type_double,
					type_float,
					type_bool,
					type_date,
					type_time,
					type_timetz,
					type_timestamp,
					type_timestamptz
				)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		', [
			'{1,null,2}',
			'{2,NULL,3}',
			'{3,NULL,4}',
			'{4,NULL,5}',
			'{1.1,NULL,2.2}',
			'{2.2,NULL,3.3}',
			'{3.3,NULL,4.4}',
			'{4.4,NULL,5.5}',
			'{5.5,NULL,6.6}',
			'{TRUE,NULL,FALSE}',
			'{NULL,\'2018-01-01\'}',
			'{NULL,\'20:30:00\'}',
			'{NULL,\'20:30:00+02\'}',
			'{NULL,\'2018-01-01 20:30:00\'}',
			'{NULL,\'2018-01-01 20:30:00+02\'}',
		]);

		$row = $this->fetch();

		Tester\Assert::same(1, $row->type_integer[0]);
		Tester\Assert::null($row->type_integer[1]);
		Tester\Assert::same(2, $row->type_integer[2]);

		Tester\Assert::same(2, $row->type_bigint[0]);
		Tester\Assert::null($row->type_bigint[1]);
		Tester\Assert::same(3, $row->type_bigint[2]);

		Tester\Assert::same(3, $row->type_smallint[0]);
		Tester\Assert::null($row->type_smallint[1]);
		Tester\Assert::same(4, $row->type_smallint[2]);

		Tester\Assert::same(4, $row->type_oid[0]);
		Tester\Assert::null($row->type_oid[1]);
		Tester\Assert::same(5, $row->type_oid[2]);

		Tester\Assert::same(1.1, $row->type_numeric[0]);
		Tester\Assert::null($row->type_numeric[1]);
		Tester\Assert::same(2.2, $row->type_numeric[2]);

		Tester\Assert::same(2.2, $row->type_decimal[0]);
		Tester\Assert::null($row->type_decimal[1]);
		Tester\Assert::same(3.3, $row->type_decimal[2]);

		Tester\Assert::same(3.3, $row->type_real[0]);
		Tester\Assert::null($row->type_real[1]);
		Tester\Assert::same(4.4, $row->type_real[2]);

		Tester\Assert::same(4.4, $row->type_double[0]);
		Tester\Assert::null($row->type_double[1]);
		Tester\Assert::same(5.5, $row->type_double[2]);

		Tester\Assert::same(5.5, $row->type_float[0]);
		Tester\Assert::null($row->type_float[1]);
		Tester\Assert::same(6.6, $row->type_float[2]);

		Tester\Assert::true($row->type_bool[0]);
		Tester\Assert::null($row->type_bool[1]);
		Tester\Assert::false($row->type_bool[2]);

		\assert(\is_array($row->type_date));
		Tester\Assert::null($row->type_date[0]);
		\assert($row->type_date[1] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01', $row->type_date[1]->format('Y-m-d'));

		Tester\Assert::null($row->type_time[0]);
		Tester\Assert::same('20:30:00', $row->type_time[1]);

		Tester\Assert::null($row->type_timetz[0]);
		Tester\Assert::same('20:30:00+02', $row->type_timetz[1]);

		\assert(\is_array($row->type_timestamp));
		Tester\Assert::null($row->type_timestamp[0]);
		\assert($row->type_timestamp[1] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00', $row->type_timestamp[1]->format('Y-m-d H:i:s'));

		\assert(\is_array($row->type_timestamptz));
		Tester\Assert::null($row->type_timestamptz[0]);
		\assert($row->type_timestamptz[1] instanceof \DateTimeImmutable);
		Tester\Assert::same('2018-01-01 20:30:00+02:00', $row->type_timestamptz[1]->setTimezone(new \DateTimeZone('+2:00'))->format('Y-m-d H:i:sP'));
	}


	public function testParseBlankArrays(): void
	{
		Tester\Assert::same([], $this->connection->query('SELECT ARRAY[]::integer[] AS arr')->fetchSingle());
	}


	public function testParseHstore(): void
	{
		$this->connection->query('CREATE EXTENSION hstore;');

		$this->connection->query('
			CREATE TABLE test(
				type_hstore hstore
			);
		');

		$this->connection->queryArgs('INSERT INTO test(type_hstore) VALUES (?)', ['a=>1']);

		$row = $this->fetch();

		Tester\Assert::exception(static function () use ($row): void {
			$row->type_hstore;
		}, Db\Exceptions\DataTypeParserException::class, NULL, Db\Exceptions\DataTypeParserException::TRY_USE_CONVERT_TO_JSON);
	}


	public function testParseNonSupportedType(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_point point
			);
		');

		$this->connection->queryArgs('INSERT INTO test(type_point) VALUES (?)', ['(1,2)']);

		$row = $this->fetch();

		Tester\Assert::exception(static function () use ($row): void {
			$row->type_point;
		}, Db\Exceptions\DataTypeParserException::class, NULL, Db\Exceptions\DataTypeParserException::CANT_PARSE_TYPE);
	}


	public function testArrayConvertToJson(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_tsvector tsvector[]
			);
		');

		$this->connection->queryArgs('INSERT INTO test(type_tsvector) VALUES (?)', ['{\'text\'}']);

		$row = $this->fetch();

		Tester\Assert::exception(static function () use ($row): void {
			$row->type_tsvector;
		}, Db\Exceptions\DataTypeParserException::class, NULL, Db\Exceptions\DataTypeParserException::TRY_USE_CONVERT_TO_JSON);
	}


	public function testArrayNonSupportedType(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				type_money money[]
			);
		');

		$this->connection->queryArgs('INSERT INTO test(type_money) VALUES (?)', ['{1)}']);

		$row = $this->fetch();

		Tester\Assert::exception(static function () use ($row): void {
			$row->type_money;
		}, Db\Exceptions\DataTypeParserException::class, NULL, Db\Exceptions\DataTypeParserException::CANT_PARSE_TYPE);
	}


	public function testCustomDataTypeParser(): void
	{
		$this->connection->setDataTypeParser(new class implements Db\DataTypeParser {

			public function parse(string $type, string|NULL $value): mixed
			{
				if (($type === 'point') && ($value !== NULL)) {
					return \array_map('intval', \explode(',', \substr($value, 1, -1), 2));
				}
				return $value;
			}

		});

		$this->connection->query('
			CREATE TABLE test(
				type_point point
			);
		');

		$this->connection->queryArgs('INSERT INTO test(type_point) VALUES (?)', ['(1,2)']);

		$row = $this->fetch();

		Tester\Assert::same([1, 2], $row->type_point);
	}


	private function fetch(): Db\Row
	{
		$row = $this->connection->query('SELECT * FROM test')->fetch();
		if ($row === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}
		return $row;
	}

}

(new ParseDataTypeTest())->run();
