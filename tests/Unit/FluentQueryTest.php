<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Fluent;
use Forrest79\PhPgSql\Tests;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class FluentQueryTest extends Tests\TestCase
{

	public function testSelect(): void
	{
		$query = $this->query()
			->select(['column'])
			->distinct()
			->from('table', 't')
			->where('column', 100)
			->where('text', NULL)
			->groupBy('column')
			->orderBy('column')
			->limit(10)
			->offset(20)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT DISTINCT column FROM table AS t WHERE (column = $1) AND (text IS NULL) GROUP BY column ORDER BY column LIMIT $2 OFFSET $3', $query->getSql());
		Tester\Assert::same([100, 10, 20], $query->getParams());
	}


	public function testSelectWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['column' => $this->query()->select([1])])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT (SELECT 1) AS "column"', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectWithQuery(): void
	{
		$query = $this->query()
			->select(['column' => new Db\Sql\Query('SELECT 1')])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT (SELECT 1) AS "column"', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectColumnAliases(): void
	{
		$query = $this->query()
			->select(['column' => 'another', 'next', 10 => 'column_with_integer_key', '1' => 'column_with_integer_in_string_key', 'a' => 'b'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT another AS "column", next, column_with_integer_key, column_with_integer_in_string_key, b AS "a"', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectBoolNull(): void
	{
		$query = $this->query()
			->select(['is_true' => TRUE, 'is_false' => FALSE, 'is_null' => NULL])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT TRUE AS "is_true", FALSE AS "is_false", NULL AS "is_null"', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from($this->query()->select(['column' => 1]), 'x')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM (SELECT 1 AS "column") AS x', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from(new Db\Sql\Query('SELECT 1 AS column'), 'x')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM (SELECT 1 AS column) AS x', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithParameter(): void
	{
		$query = $this->query()
			->select(['gs'])
			->from(Db\Sql\Expression::create('generate_series(?::integer, ?::integer, ?::integer)', 2, 1, -1), 'gs')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT gs FROM generate_series($1::integer, $2::integer, $3::integer) AS gs', $query->getSql());
		Tester\Assert::same([2, 1, -1], $query->getParams());
	}


	public function testMoreFrom(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table1', 't1')
			->from('table2', 't2')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table1 AS t1, table2 AS t2', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testWhereSimple(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->where('x.column = t.id')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testWhereParameters(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->where('x.column', 1)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE x.column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testWhereWithComplex(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.column = t.id')
			->add('x.id', [1, 2]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->where($complex)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE (x.column = t.id) AND (x.id IN ($1, $2))', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testWhereWithSql(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->where(Db\Sql\Expression::create('x.id', [1, 2]))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE x.id IN ($1, $2)', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testWhereWithBadType(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->where(['x.id = 1']);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::UNSUPPORTED_CONDITION_TYPE);

		Tester\Assert::exception(function (): void {
			$this->query()->where(NULL);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::UNSUPPORTED_CONDITION_TYPE);
	}


	public function testWhereWithBadTypeWithParameters(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->where(Db\Sql\Expression::create('x.column = ?'), 1);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::ONLY_STRING_CONDITION_CAN_HAVE_PARAMS);
	}


	public function testWhereAnd(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.type = t.id')
			->add('x.test', [3, 5]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->whereAnd([
				'x.column = t.id',
				['x.column', 1],
				$complex,
				Db\Sql\Expression::create('x.id', 7),
			])
			->query()
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE (x.column = t.id) AND (x.column = $1) AND ((x.type = t.id) AND (x.test IN ($2, $3))) AND (x.id = $4)', $query->getSql());
		Tester\Assert::same([1, 3, 5, 7], $query->getParams());
	}


	public function testWhereAndContinue(): void
	{
		$sourceQuery = $this->query()
			->select(['x.column'])
			->from('table', 't');

		$complex = $sourceQuery->whereAnd([
			'x.column = t.id',
			Db\Sql\Expression::create('x.id', 7),
		]);

		$complex
			->add(Fluent\Complex::createAnd()->add('x.type = t.id')->add('x.test', [3, 5]))
			->add('x.column', 1);

		$query = $sourceQuery
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE (x.column = t.id) AND (x.id = $1) AND ((x.type = t.id) AND (x.test IN ($2, $3))) AND (x.column = $4)', $query->getSql());
		Tester\Assert::same([7, 3, 5, 1], $query->getParams());
	}


	public function testWhereOr(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.type = t.id')
			->add('x.test', [3, 5]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->whereOr([
				'x.column = t.id',
				['x.column', 1],
				$complex,
				Db\Sql\Expression::create('x.id', 7),
			])
			->query()
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t WHERE (x.column = t.id) OR (x.column = $1) OR ((x.type = t.id) AND (x.test IN ($2, $3))) OR (x.id = $4)', $query->getSql());
		Tester\Assert::same([1, 3, 5, 7], $query->getParams());
	}


	public function testHavingSimple(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->having('x.column = t.id')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testHavingParameters(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->having('x.column', 1)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING x.column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testHavingWithComplex(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.column = t.id')
			->add('x.id', [1, 2]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->having($complex)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING (x.column = t.id) AND (x.id IN ($1, $2))', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testHavingWithSql(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->having(Db\Sql\Expression::create('x.id', [1, 2]))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING x.id IN ($1, $2)', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testHavingWithBadType(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->having(new Db\Query('x.id = 1', []));
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::UNSUPPORTED_CONDITION_TYPE);
	}


	public function testHavingWithBadTypeWithParameters(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->having(Db\Sql\Expression::create('x.column = ?'), 1);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::ONLY_STRING_CONDITION_CAN_HAVE_PARAMS);
	}


	public function testHavingAnd(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.type = t.id')
			->add('x.test', [3, 5]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->havingAnd([
				'x.column = t.id',
				['x.column', 1],
				$complex,
				Db\Sql\Expression::create('x.id', 7),
			])
			->query()
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING (x.column = t.id) AND (x.column = $1) AND ((x.type = t.id) AND (x.test IN ($2, $3))) AND (x.id = $4)', $query->getSql());
		Tester\Assert::same([1, 3, 5, 7], $query->getParams());
	}


	public function testHavingOr(): void
	{
		$complex = Fluent\Complex::createAnd()
			->add('x.type = t.id')
			->add('x.test', [3, 5]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->havingOr([
				'x.column = t.id',
				['x.column', 1],
				$complex,
				Db\Sql\Expression::create('x.id', 7),
			])
			->query()
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING (x.column = t.id) OR (x.column = $1) OR ((x.type = t.id) AND (x.test IN ($2, $3))) OR (x.id = $4)', $query->getSql());
		Tester\Assert::same([1, 3, 5, 7], $query->getParams());
	}


	public function testHavingOrContinue(): void
	{
		$sourceQuery = $this->query()
			->select(['x.column'])
			->from('table', 't');

		$complex = $sourceQuery->havingOr([
			'x.column = t.id',
			Db\Sql\Expression::create('x.id', 7),
		]);

		$complex
			->add(Fluent\Complex::createAnd()->add('x.type = t.id')->add('x.test', [3, 5]))
			->add('x.column', 1);

		$query = $sourceQuery
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t HAVING (x.column = t.id) OR (x.id = $1) OR ((x.type = t.id) AND (x.test IN ($2, $3))) OR (x.column = $4)', $query->getSql());
		Tester\Assert::same([7, 3, 5, 1], $query->getParams());
	}


	public function testJoinWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join($this->query()->select(['column' => 1]), 'x', 'x.column = t.id')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN (SELECT 1 AS "column") AS x ON x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join(new Db\Sql\Query('SELECT 1 AS column'), 'x', 'x.column = t.id')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN (SELECT 1 AS column) AS x ON x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithStringOn(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', 'x.column = t.id')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithComplexOn(): void
	{
		$complexOn = Fluent\Complex::createAnd()
			->add('x.column = t.id')
			->add('x.id', [1, 2]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', $complexOn)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id IN ($1, $2))', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testJoinWithSqlOn(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', Db\Sql\Expression::create('(x.column = t.id) AND (x.id = ?)', 2))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id = $1)', $query->getSql());
		Tester\Assert::same([2], $query->getParams());
	}


	public function testJoinWithBadTypeOn(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->join('another', 'x', ['x.column = t.id']);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::UNSUPPORTED_CONDITION_TYPE);
	}


	public function testJoinWithAddOn(): void
	{
		$complexOn = Fluent\Complex::createOr()
			->add('x.complex_id = t.id')
			->add('x.complex_id', [3, 4]);

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', 'x.column = t.id')
				->on('x', 'x.id = 1')
				->on('x', 'x.id = ?', 2)
				->on('x', Db\Sql\Expression::create('x.type_id = ?', 'test'))
				->on('x', $complexOn)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id = 1) AND (x.id = $1) AND (x.type_id = $2) AND ((x.complex_id = t.id) OR (x.complex_id IN ($3, $4)))', $query->getSql());
		Tester\Assert::same([2, 'test', 3, 4], $query->getParams());
	}


	public function testJoinWithAddOnWithBadType(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->on('x', ['x.id = 1']);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::UNSUPPORTED_CONDITION_TYPE);
	}


	public function testJoinWithBadTypeWithParameters(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->on('x', Db\Sql\Expression::create('x.column = ?'), 1);
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::ONLY_STRING_CONDITION_CAN_HAVE_PARAMS);
	}


	public function testJoinNoOn(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select(['x.column'])
				->from('table', 't')
				->join('another', 'x')
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_JOIN_CONDITIONS);
	}


	public function testSelectCombine(): void
	{
		$query = $this->query()
			->from('table', 't')
			->select(['column'])
			->union('SELECT column FROM table2 AS t2')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectCombineFluent(): void
	{
		$query = $this->query()
			->from('table', 't')
			->select(['column'])
			->union(
				$this->query()
					->select(['column'])
					->from('table2', 't2')
			)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectCombineQuery(): void
	{
		$query = $this->query()
			->from('table', 't')
			->select(['column'])
			->union(new Db\Sql\Query('SELECT column FROM table2 AS t2'))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectNoColumns(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->from('table')
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_COLUMNS_TO_SELECT);
	}


	public function testOrderByFluent(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table', 't')
			->orderBy($this->query()->select(['sort_by_value(column)']))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table AS t ORDER BY (SELECT sort_by_value(column))', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testOrderByQuery(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table', 't')
			->orderBy(Db\Sql\Query::create('sort_by_value(column)'))
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table AS t ORDER BY (sort_by_value(column))', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertRow(): void
	{
		$query = $this->query()
			->values([
				'column' => 1,
				'column_from' => Db\Sql\Literal::create('3'),
				'column_fluent_query' => $this->query()->select(['\'test_fluent\''])->where('4', 4),
				'column_query' => new Db\Sql\Query('SELECT \'test\' WHERE 5 = ?', [5]),
			])
			->insert('table')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column, column_from, column_fluent_query, column_query) VALUES($1, 3, (SELECT \'test_fluent\' WHERE 4 = $2), (SELECT \'test\' WHERE 5 = $3)) RETURNING column', $query->getSql());
		Tester\Assert::same([1, 4, 5], $query->getParams());
	}


	public function testInsertMergeData(): void
	{
		$query = $this->query()
			->insert('table')
			->values([
				'column1' => 3,
				'column2' => -2, // this will be ignored, last added column have bigger priority
			])
			->values([
				'column2' => 2,
				'column3' => 1,
			])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column2, column3, column1) VALUES($1, $2, $3)', $query->getSql());
		Tester\Assert::same([2, 1, 3], $query->getParams());
	}


	public function testInsertRows(): void
	{
		$query = $this->query()
			->rows([
				['column' => 1],
				['column' => 2],
				['column' => 3],
				['column' => Db\Sql\Literal::create('4')],
				['column' => $this->query()->select(['\'test_fluent\''])->where('6', 6)],
				['column' => new Db\Sql\Query('SELECT \'test\' WHERE 7 = ?', [7])],
			])
			->insert('table')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1), ($2), ($3), (4), ((SELECT \'test_fluent\' WHERE 6 = $4)), ((SELECT \'test\' WHERE 7 = $5)) RETURNING column', $query->getSql());
		Tester\Assert::same([1, 2, 3, 6, 7], $query->getParams());
	}


	public function testInsertRowsMergeData(): void
	{
		$query = $this->query()
			->rows([
				['column' => 1],
				['column' => 2],
			])
			->rows([
				['column' => 3],
				['column' => 4],
			])
			->insert('table')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1), ($2), ($3), ($4)', $query->getSql());
		Tester\Assert::same([1, 2, 3, 4], $query->getParams());
	}


	public function testInsertSelect(): void
	{
		$query = $this->query()
			->insert('table', ['name'])
			->select(['column'])
			->from('table2', 't2')
			->returning(['name'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(name) SELECT column FROM table2 AS t2 RETURNING name', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertSelectDetectColumnsFromSelect(): void
	{
		$query = $this->query()
			->insert('table')
			->select(['column', 'name' => 'column2'])
			->from('table2', 't2')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column, name) SELECT column, column2 AS "name" FROM table2 AS t2 RETURNING column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertRowWithArray(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->insert('table')
				->values([
					'column' => [1, 2],
				])
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::DATA_CANT_CONTAIN_ARRAY);
	}


	public function testInsertRowsWithArray(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->insert('table')
				->rows([
					['column' => [1, 2]],
				])
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::DATA_CANT_CONTAIN_ARRAY);
	}


	public function testInsertNoData(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->insert('table')
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_DATA_TO_INSERT);
	}


	public function testUpdate(): void
	{
		$query = $this->query()
			->update('table', 't')
			->set([
				'column' => 1,
				'column_from' => Db\Sql\Literal::create('t2.id'),
				'column_fluent_query' => $this->query()->select(['\'test_fluent\''])->where('2', 2),
				'column_query' => new Db\Sql\Query('SELECT \'test\' WHERE 3 = ?', [3]),
			])
			->from('table2', 't2')
			->where('t2.column', 100)
			->returning(['t.column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('UPDATE table AS t SET column = $1, column_from = t2.id, column_fluent_query = (SELECT \'test_fluent\' WHERE 2 = $2), column_query = (SELECT \'test\' WHERE 3 = $3) FROM table2 AS t2 WHERE t2.column = $4 RETURNING t.column', $query->getSql());
		Tester\Assert::same([1, 2, 3, 100], $query->getParams());
	}


	public function testUpdateMergeData(): void
	{
		$query = $this->query()
			->update('table', 't')
			->set([
				'column1' => 3,
				'column2' => -2, // this will be ignored, last added column have bigger priority
			])
			->set([
				'column2' => 2,
				'column3' => 1,
			])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('UPDATE table AS t SET column2 = $1, column3 = $2, column1 = $3', $query->getSql());
		Tester\Assert::same([2, 1, 3], $query->getParams());
	}


	public function testUpdateWithArray(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->update('table')
				->set([
					'column1' => [1, 2],
				])
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::DATA_CANT_CONTAIN_ARRAY);
	}


	public function testUpdateNoData(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->update('table')
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_DATA_TO_UPDATE);
	}


	public function testNoMainTable(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->update()
				->set(['column' => 1])
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_MAIN_TABLE);
	}


	public function testDelete(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => 'column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING column AS "c"', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testTruncate(): void
	{
		$query = $this->query()->truncate('table')->createSqlQuery()->createQuery();
		Tester\Assert::same('TRUNCATE table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testReturningFluentQuery(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => $this->query()->select(['to_value(column)'])])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING (SELECT to_value(column)) AS "c"', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testReturningQuery(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => new Db\Sql\Query('to_value(column)')])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING (to_value(column)) AS "c"', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testParamsPrefix(): void
	{
		$withQuery = $this->query()
			->select(['columnWith'])
			->from('tableWith')
			->where('columnWith > ?', 5);

		$query = $this->query()
			->select(['column'])
			->from('table')
			->where('column', 100)
			->prefix('WITH cte AS (?)', $withQuery)
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('WITH cte AS (SELECT columnWith FROM tableWith WHERE columnWith > $1) SELECT column FROM table WHERE column = $2', $query->getSql());
		Tester\Assert::same([5, 100], $query->getParams());
	}


	public function testSimpleSuffix(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table')
			->where('column', 100)
			->sufix('FOR UPDATE')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT column FROM table WHERE column = $1 FOR UPDATE', $query->getSql());
		Tester\Assert::same([100], $query->getParams());

		$query = $this->query()
			->truncate('table')
			->sufix('CASCADE')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('TRUNCATE table CASCADE', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSuffixWithReturning(): void
	{
		$query = $this->query()
			->insert('table')
			->values(['column' => 'value'])
			->sufix('ON CONFLICT (column) DO NOTHING')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1) ON CONFLICT (column) DO NOTHING RETURNING column', $query->getSql());
		Tester\Assert::same(['value'], $query->getParams());

		$query = $this->query()
			->update('table')
			->set(['column' => 'value'])
			->sufix('WHERE CURRENT OF cursor_name')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('UPDATE table SET column = $1 WHERE CURRENT OF cursor_name RETURNING column', $query->getSql());
		Tester\Assert::same(['value'], $query->getParams());

		$query = $this->query()
			->delete('table')
			->sufix('WHERE CURRENT OF cursor_name')
			->returning(['column'])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('DELETE FROM table WHERE CURRENT OF cursor_name RETURNING column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testComplexWhere(): void
	{
		$complexOr = $this->query()->whereOr();
		$complexOr->add('column', 1);
		$complexOr->add('column2', [2, 3]);
		$complexAnd = $complexOr->addComplexAnd();
		$complexAnd->add('column', $this->query()->select([1]));
		$complexAnd->add('column2 = ANY(?)', new Db\Sql\Query('SELECT 2'));
		$complexOr->add('column3 IS NOT NULL');

		$query = $complexOr->query()
			->select(['*'])
			->from('table')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT * FROM table WHERE (column = $1) OR (column2 IN ($2, $3)) OR ((column IN (SELECT 1)) AND (column2 = ANY(SELECT 2))) OR (column3 IS NOT NULL)', $query->getSql());
		Tester\Assert::same([1, 2, 3], $query->getParams());
	}


	public function testComplexHaving(): void
	{
		$complexOr = $this->query()->havingOr();
		$complexOr->add('column', 1);
		$complexOr->add('column2', [2, 3]);
		$complexAnd = $complexOr->addComplexAnd();
		$complexAnd->add('column', $this->query()->select([1]));
		$complexAnd->add('column2 = ANY(?)', new Db\Sql\Query('SELECT 2'));
		$complexOr->add('column3 IS NOT NULL');

		$query = $complexOr->query()
			->select(['*'])
			->from('table')
			->groupBy('column', 'column2')
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT * FROM table GROUP BY column, column2 HAVING (column = $1) OR (column2 IN ($2, $3)) OR ((column IN (SELECT 1)) AND (column2 = ANY(SELECT 2))) OR (column3 IS NOT NULL)', $query->getSql());
		Tester\Assert::same([1, 2, 3], $query->getParams());
	}


	public function testComplexBadParams(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->whereOr()
					->add('columns = ? AND column2 = ?', 1, 2, 3)
				->query()
				->select(['*'])
				->createSqlQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::BAD_PARAMS_COUNT);
	}


	public function testOnlyOneMainTable(): void
	{
		$query = $this->query()->table('table');

		Tester\Assert::exception(static function () use ($query): void {
			$query->table('another');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::ONLY_ONE_MAIN_TABLE);
	}


	public function testTableAliasAlreadyExists(): void
	{
		$query = $this->query()->table('table', 't');

		Tester\Assert::exception(static function () use ($query): void {
			$query->from('another', 't');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::TABLE_ALIAS_ALREADY_EXISTS);
	}


	public function testHas(): void
	{
		$query = $this->query();

		Tester\Assert::false($query->has($query::PARAM_SELECT));
		Tester\Assert::false($query->has($query::PARAM_DISTINCT));
		Tester\Assert::false($query->has($query::PARAM_TABLES));
		Tester\Assert::false($query->has($query::PARAM_TABLE_TYPES));
		Tester\Assert::false($query->has($query::PARAM_JOIN_CONDITIONS));
		Tester\Assert::false($query->has($query::PARAM_WHERE));
		Tester\Assert::false($query->has($query::PARAM_GROUPBY));
		Tester\Assert::false($query->has($query::PARAM_HAVING));
		Tester\Assert::false($query->has($query::PARAM_ORDERBY));
		Tester\Assert::false($query->has($query::PARAM_LIMIT));
		Tester\Assert::false($query->has($query::PARAM_OFFSET));
		Tester\Assert::false($query->has($query::PARAM_COMBINE_QUERIES));
		Tester\Assert::false($query->has($query::PARAM_INSERT_COLUMNS));
		Tester\Assert::false($query->has($query::PARAM_RETURNING));
		Tester\Assert::false($query->has($query::PARAM_DATA));
		Tester\Assert::false($query->has($query::PARAM_ROWS));
		Tester\Assert::false($query->has($query::PARAM_PREFIX));
		Tester\Assert::false($query->has($query::PARAM_SUFFIX));

		$query
			->select(['column'])
			->distinct()
			->from('table', 't')
			->leftJoin('join_table', 'j', 'j.id = t.join_id')
			->where('t.column', 100)
			->where('t.text', NULL)
			->groupBy('t.column')
			->having('COUNT(*) > 1')
			->orderBy('t.column')
			->limit(10)
			->offset(20)
			->prefix('some SQL prefix')
			->sufix('some SQL suffix')
			->union('SELECT 1');

		Tester\Assert::true($query->has($query::PARAM_SELECT));
		Tester\Assert::true($query->has($query::PARAM_DISTINCT));
		Tester\Assert::true($query->has($query::PARAM_TABLES));
		Tester\Assert::true($query->has($query::PARAM_TABLE_TYPES));
		Tester\Assert::true($query->has($query::PARAM_JOIN_CONDITIONS));
		Tester\Assert::true($query->has($query::PARAM_WHERE));
		Tester\Assert::true($query->has($query::PARAM_GROUPBY));
		Tester\Assert::true($query->has($query::PARAM_HAVING));
		Tester\Assert::true($query->has($query::PARAM_ORDERBY));
		Tester\Assert::true($query->has($query::PARAM_LIMIT));
		Tester\Assert::true($query->has($query::PARAM_OFFSET));
		Tester\Assert::true($query->has($query::PARAM_COMBINE_QUERIES));
		Tester\Assert::true($query->has($query::PARAM_PREFIX));
		Tester\Assert::true($query->has($query::PARAM_SUFFIX));

		$query = $this->query()->insert('table', ['column'])->select(['1'])->returning(['column']);

		Tester\Assert::true($query->has($query::PARAM_INSERT_COLUMNS));
		Tester\Assert::true($query->has($query::PARAM_RETURNING));

		$query = $this->query()->insert('table')->rows([
			['column' => 1],
		]);

		Tester\Assert::true($query->has($query::PARAM_ROWS));

		$query = $this->query()->update('table')->set(['column' => 'data']);

		Tester\Assert::true($query->has($query::PARAM_DATA));
	}


	public function testHasBadParam(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select([1])
				->has('table');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::NON_EXISTING_QUERY_PARAM);
	}


	public function testReset(): void
	{
		$query = $this->query()
			->select([1]);

		$sql = $query->createSqlQuery()->createQuery();

		Tester\Assert::same('SELECT 1', $sql->getSql());
		Tester\Assert::same([], $sql->getParams());

		$query2 = $query
			->reset(Fluent\Query::PARAM_SELECT)
			->select([2])
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT 2', $query2->getSql());
		Tester\Assert::same([], $query2->getParams());
	}


	public function testResetBadParam(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select([1])
				->reset('table');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::NON_EXISTING_QUERY_PARAM);
	}


	public function testQueyableMustHaveAlias(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->table($this->query()->select([1]));
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::SQL_MUST_HAVE_ALIAS);
	}


	public function testParamMustBeScalarOrQuery(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->table(['table'], 't');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::PARAM_MUST_BE_SCALAR_OR_EXPRESSION);
	}


	public function testBadQueryBuilderType(): void
	{
		Tester\Assert::exception(static function (): void {
			(new Fluent\QueryBuilder())->createSqlQuery('table', []);
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::BAD_QUERY_TYPE);
	}


	public function testCloneQuery(): void
	{
		$baseQuery = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another_table', 'at', 'at.id = t.another_table_id')
				->on('at', 'at.type_id', 2)
			->where('x.column', 1)
			->having('count(*) > ?', 10);

		$clonedQuery = clone $baseQuery;
		$clonedQuery
			->on('at', 'at.another_type', 3)
			->where('x.another_column', 4)
			->having('avg(column)', 1);

		$testBaseQuery = $baseQuery
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another_table AS at ON (at.id = t.another_table_id) AND (at.type_id = $1) WHERE x.column = $2 HAVING count(*) > $3', $testBaseQuery->getSql());
		Tester\Assert::same([2, 1, 10], $testBaseQuery->getParams());

		$testClonedQuery = $clonedQuery
			->createSqlQuery()
			->createQuery();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another_table AS at ON (at.id = t.another_table_id) AND (at.type_id = $1) AND (at.another_type = $2) WHERE (x.column = $3) AND (x.another_column = $4) HAVING (count(*) > $5) AND (avg(column) = $6)', $testClonedQuery->getSql());
		Tester\Assert::same([2, 3, 1, 4, 10, 1], $testClonedQuery->getParams());
	}


	private function query(): Fluent\Query
	{
		return new Fluent\Query(new Fluent\QueryBuilder());
	}

}

(new FluentQueryTest())->run();
