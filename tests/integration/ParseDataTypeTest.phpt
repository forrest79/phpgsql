<?php declare(strict_types=1);

namespace Tests\Integration\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class ParseDataTypeTest extends TestCase
{
	/** @var Db\Connection */
	private $connection;


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getTableName()), FALSE, TRUE);
		$this->connection->connect();
	}


	public function testParseBasic()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type_integer integer,
				type_bigint bigint,
				type_smallint smallint,
				type_numeric numeric,
				type_decimal decimal,
				type_real real,
				type_double double precision,
				type_float float,
				type_bool boolean,
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
				type_tsvector tsvector
			);
		');

		$this->connection->queryArray('
			INSERT INTO test(
					type_integer,
					type_bigint,
					type_smallint,
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
					type_timestamptz,
					type_varchar,
					type_text,
					type_char,
					type_json,
					type_jsonb,
					type_tsquery,
					type_tsvector
				)
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		', [
			1,
			2,
			3,
			1.1,
			2.2,
			3.3,
			4.4,
			5.5,
			TRUE,
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
		]);

		$row = $this->connection->query('SELECT * FROM test')->fetch();

		Tester\Assert::true(is_int($row->id));
		Tester\Assert::true(is_int($row->type_integer));
		Tester\Assert::true(is_int($row->type_bigint));
		Tester\Assert::true(is_int($row->type_smallint));
		Tester\Assert::true(is_float($row->type_numeric));
		Tester\Assert::true(is_float($row->type_decimal));
		Tester\Assert::true(is_float($row->type_real));
		Tester\Assert::true(is_float($row->type_double));
		Tester\Assert::true(is_float($row->type_float));
		Tester\Assert::true(is_bool($row->type_bool));
		Tester\Assert::true($row->type_date instanceof \DateTimeImmutable);
		Tester\Assert::true(is_string($row->type_time));
		Tester\Assert::true(is_string($row->type_timetz));
		Tester\Assert::true($row->type_timestamp instanceof \DateTimeImmutable);
		Tester\Assert::true($row->type_timestamptz instanceof \DateTimeImmutable);
		Tester\Assert::true(is_string($row->type_varchar));
		Tester\Assert::true(is_string($row->type_text));
		Tester\Assert::true(is_string($row->type_char));
		Tester\Assert::true(is_array($row->type_json));
		Tester\Assert::true(is_array($row->type_jsonb));
		Tester\Assert::true(is_string($row->type_tsquery));
		Tester\Assert::true(is_string($row->type_tsvector));
	}


	public function testParseArrays()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type_integer integer[],
				type_bigint bigint[],
				type_smallint smallint[],
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
				type_timestamptz timestamptz[],
				type_varchar character varying[],
				type_text text[],
				type_char char(10)[],
				type_tsquery tsquery[],
				type_tsrange tsrange[],
				type_tstzrange tstzrange[],
				type_tsvector tsvector[]
			);
		');

		$this->connection->queryArray('
			INSERT INTO test(
					type_integer,
					type_bigint,
					type_smallint,
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
			VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
		', [
			'{1}',
			'{2}',
			'{3}',
			'{1.1}',
			'{2.2}',
			'{3.3}',
			'{4.4}',
			'{5.5}',
			'{TRUE}',
			'{\'2018-01-01\'}',
			'{\'20:30:00\'}',
			'{\'20:30:00+02\'}',
			'{\'2018-01-01 20:30:00\'}',
			'{\'2018-01-01 20:30:00+02\'}',
		]);

		$row = $this->connection->query('SELECT * FROM test')->fetch();

		Tester\Assert::true(is_int($row->id));
		Tester\Assert::true(is_array($row->type_integer));
		Tester\Assert::true(is_int($row->type_integer[0]));
		Tester\Assert::true(is_array($row->type_bigint));
		Tester\Assert::true(is_int($row->type_bigint[0]));
		Tester\Assert::true(is_array($row->type_smallint));
		Tester\Assert::true(is_int($row->type_smallint[0]));
		Tester\Assert::true(is_array($row->type_numeric));
		Tester\Assert::true(is_float($row->type_numeric[0]));
		Tester\Assert::true(is_array($row->type_decimal));
		Tester\Assert::true(is_float($row->type_decimal[0]));
		Tester\Assert::true(is_array($row->type_real));
		Tester\Assert::true(is_float($row->type_real[0]));
		Tester\Assert::true(is_array($row->type_double));
		Tester\Assert::true(is_float($row->type_double[0]));
		Tester\Assert::true(is_array($row->type_float));
		Tester\Assert::true(is_float($row->type_float[0]));
		Tester\Assert::true(is_array($row->type_bool));
		Tester\Assert::true(is_bool($row->type_bool[0]));
		Tester\Assert::true(is_array($row->type_date));
		Tester\Assert::true($row->type_date[0] instanceof \DateTimeImmutable);
		Tester\Assert::true(is_array($row->type_time));
		Tester\Assert::true(is_string($row->type_time[0]));
		Tester\Assert::true(is_array($row->type_timetz));
		Tester\Assert::true(is_string($row->type_timetz[0]));
		Tester\Assert::true(is_array($row->type_timestamp));
		Tester\Assert::true($row->type_timestamp[0] instanceof \DateTimeImmutable);
		Tester\Assert::true(is_array($row->type_timestamptz));
		Tester\Assert::true($row->type_timestamptz[0] instanceof \DateTimeImmutable);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new ParseDataTypeTest())->run();
