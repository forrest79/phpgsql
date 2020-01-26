<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FluentQueryTest extends Tester\TestCase
{

	public function testSelect(): void
	{
		$query = $this->query()
			->select(['column'])
			->distinct()
			->from('table', 't')
			->where('column', 100)
			->where('text', NULL)
			->groupBy(['column'])
			->orderBy(['column'])
			->limit(10)
			->offset(20)
			->prepareSql();

		Tester\Assert::same('SELECT DISTINCT column FROM table AS t WHERE (column = $1) AND (text IS NULL) GROUP BY column ORDER BY column LIMIT $2 OFFSET $3', $query->getSql());
		Tester\Assert::same([100, 10, 20], $query->getParams());
	}


	public function testSelectWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['column' => $this->query()->select([1])])
			->prepareSql();

		Tester\Assert::same('SELECT (SELECT 1) AS column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectWithQuery(): void
	{
		$query = $this->query()
			->select(['column' => new Db\Query('SELECT 1')])
			->prepareSql();

		Tester\Assert::same('SELECT (SELECT 1) AS column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectColumnAliases(): void
	{
		$query = $this->query()
			->select(['column' => 'another', 'next', 10 => 'column_with_integer_key', '1' => 'column_with_integer_in_string_key', 'a' => 'b'])
			->prepareSql();

		Tester\Assert::same('SELECT another AS column, next, column_with_integer_key, column_with_integer_in_string_key, b AS a', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from($this->query()->select(['column' => 1]), 'x')
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM (SELECT 1 AS column) AS x', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from(new Db\Query('SELECT 1 AS column'), 'x')
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM (SELECT 1 AS column) AS x', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFromWithLiteral(): void
	{
		$query = $this->query()
			->select(['gs'])
			->from(Db\Literal::create('generate_series(?::integer, ?::integer, ?::integer)', 2, 1, -1), 'gs')
			->prepareSql();

		Tester\Assert::same('SELECT gs FROM generate_series($1::integer, $2::integer, $3::integer) AS gs', $query->getSql());
		Tester\Assert::same([2, 1, -1], $query->getParams());
	}


	public function testFromWithBadQueryable(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select(['qa'])
				->from(new class implements Db\Queryable {

					function getSql(): string
					{
						return '';
					}

					function getParams(): array
					{
						return [];
					}

				}, 'qa')
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::BAD_QUERYABLE);
	}


	public function testJoinWithFluentQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join($this->query()->select(['column' => 1]), 'x', 'x.column = t.id')
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN (SELECT 1 AS column) AS x ON x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithQuery(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join(new Db\Query('SELECT 1 AS column'), 'x', 'x.column = t.id')
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN (SELECT 1 AS column) AS x ON x.column = t.id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithArrayOn(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', [['x.column = t.id'], ['x.id = 2']])
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id = 2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoinWithArrayParamsOn(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', [['x.column = t.id'], ['x.id', 2]])
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id = $1)', $query->getSql());
		Tester\Assert::same([2], $query->getParams());
	}


	public function testJoinWithAddOn(): void
	{
		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', 'x.column = t.id')
				->on('x', ['x.id', 2])
				->on('x', 'x.id = 3')
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON (x.column = t.id) AND (x.id = $1) AND (x.id = 3)', $query->getSql());
		Tester\Assert::same([2], $query->getParams());
	}


	public function testJoinWithComplexOn(): void
	{
		$complexOn = Fluent\Complex::createOr()
			->addComplexAnd()
				->add('x.column = t.id')
				->add('x.id', [1, 2])
				->parent()
			->add('x.column = 3');

		$query = $this->query()
			->select(['x.column'])
			->from('table', 't')
			->join('another', 'x', $complexOn)
			->prepareSql();

		Tester\Assert::same('SELECT x.column FROM table AS t INNER JOIN another AS x ON ((x.column = t.id) AND (x.id IN ($1, $2))) OR (x.column = 3)', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testJoinNoOn(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select(['x.column'])
				->from('table', 't')
				->join('another', 'x')
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_JOIN_CONDITIONS);
	}


	public function testSelectCombine(): void
	{
		$query = $this->query()
			->from('table', 't')
			->select(['column'])
			->union('SELECT column FROM table2 AS t2')
			->prepareSql();

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
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectCombineQuery(): void
	{
		$query = $this->query()
			->from('table', 't')
			->select(['column'])
			->union(new Db\Query('SELECT column FROM table2 AS t2'))
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelectNoColumns(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->from('table')
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_COLUMNS_TO_SELECT);
	}


	public function testOrderByFluent(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table', 't')
			->orderBy([$this->query()->select(['sort_by_value(column)'])])
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table AS t ORDER BY (SELECT sort_by_value(column))', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testOrderByQuery(): void
	{
		$query = $this->query()
			->select(['column'])
			->from('table', 't')
			->orderBy([new Db\Query('sort_by_value(column)')])
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table AS t ORDER BY (sort_by_value(column))', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertRow(): void
	{
		$query = $this->query()
			->values([
				'column' => 1,
			])
			->insert('table')
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1) RETURNING column', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
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
			->prepareSql();

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
			])
			->insert('table')
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1), ($2), ($3) RETURNING column', $query->getSql());
		Tester\Assert::same([1, 2, 3], $query->getParams());
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
			->prepareSql();

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
			->prepareSql();

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
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column, name) SELECT column, column2 AS name FROM table2 AS t2 RETURNING column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertNoData(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->insert('table')
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_DATA_TO_INSERT);
	}


	public function testUpdate(): void
	{
		$query = $this->query()
			->update('table', 't')
			->set([
				'column' => 1,
				'column_from' => Db\Literal::create('t2.id'),
			])
			->from('table2', 't2')
			->where('t2.column', 100)
			->returning(['t.column'])
			->prepareSql();

		Tester\Assert::same('UPDATE table AS t SET column = $1, column_from = t2.id FROM table2 AS t2 WHERE t2.column = $2 RETURNING t.column', $query->getSql());
		Tester\Assert::same([1, 100], $query->getParams());
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
			->prepareSql();

		Tester\Assert::same('UPDATE table AS t SET column2 = $1, column3 = $2, column1 = $3', $query->getSql());
		Tester\Assert::same([2, 1, 3], $query->getParams());
	}


	public function testUpdateNoData(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->update('table')
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_DATA_TO_UPDATE);
	}


	public function testNoMainTable(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->update()
				->set(['column' => 1])
				->prepareSql();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::NO_MAIN_TABLE);
	}


	public function testDelete(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => 'column'])
			->prepareSql();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING column AS c', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testTruncate(): void
	{
		$query = $this->query()->truncate('table')->prepareSql();
		Tester\Assert::same('TRUNCATE table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testReturningFluentQuery(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => $this->query()->select(['to_value(column)'])])
			->prepareSql();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING (SELECT to_value(column)) AS c', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testReturningQuery(): void
	{
		$query = $this->query()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => new Db\Query('to_value(column)')])
			->prepareSql();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING (to_value(column)) AS c', $query->getSql());
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
			->prepareSql();

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
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table WHERE column = $1 FOR UPDATE', $query->getSql());
		Tester\Assert::same([100], $query->getParams());

		$query = $this->query()
			->truncate('table')
			->sufix('CASCADE')
			->prepareSql();

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
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1) ON CONFLICT (column) DO NOTHING RETURNING column', $query->getSql());
		Tester\Assert::same(['value'], $query->getParams());

		$query = $this->query()
			->update('table')
			->set(['column' => 'value'])
			->sufix('WHERE CURRENT OF cursor_name')
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('UPDATE table SET column = $1 WHERE CURRENT OF cursor_name RETURNING column', $query->getSql());
		Tester\Assert::same(['value'], $query->getParams());

		$query = $this->query()
			->delete('table')
			->sufix('WHERE CURRENT OF cursor_name')
			->returning(['column'])
			->prepareSql();

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
		$complexAnd->add('column2 = ANY(?)', new Db\Query('SELECT 2'));
		$complexOr->add('column3 IS NOT NULL');

		$query = $complexOr->query()
			->select(['*'])
			->from('table')
			->prepareSql();

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
		$complexAnd->add('column2 = ANY(?)', new Db\Query('SELECT 2'));
		$complexOr->add('column3 IS NOT NULL');

		$query = $complexOr->query()
			->select(['*'])
			->from('table')
			->groupBy(['column', 'column2'])
			->prepareSql();

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
				->prepareSql();
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


	public function testReset(): void
	{
		$query = $this->query()
			->select([1]);

		$sql = $query->prepareSql();

		Tester\Assert::same('SELECT 1', $sql->getSql());
		Tester\Assert::same([], $sql->getParams());

		$query2 = $query
			->reset(Fluent\Query::PARAM_SELECT)
			->select([2])
			->prepareSql();

		Tester\Assert::same('SELECT 2', $query2->getSql());
		Tester\Assert::same([], $query2->getParams());
	}


	public function testResetBadParam(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->select([1])
				->reset('table');
		}, Fluent\Exceptions\QueryException::class);
	}


	public function testQueyableMustHaveAlias(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()
				->table($this->query()->select([1]));
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::QUERYABLE_MUST_HAVE_ALIAS);
	}


	public function testParamMustBeScalarOrQueryable(): void
	{
		Tester\Assert::exception(function (): void {
			$this->query()->table(['table'], 't');
		}, Fluent\Exceptions\QueryException::class, NULL, Fluent\Exceptions\QueryException::PARAM_MUST_BE_SCALAR_OR_QUERYABLE);
	}


	public function testBadQueryBuilderType(): void
	{
		Tester\Assert::exception(static function (): void {
			(new Fluent\QueryBuilder('table', []))->createQuery();
		}, Fluent\Exceptions\QueryBuilderException::class, NULL, Fluent\Exceptions\QueryBuilderException::BAD_QUERY_TYPE);
	}


	private function query(): Fluent\Query
	{
		return new Fluent\Query();
	}

}

\run(FluentQueryTest::class);
