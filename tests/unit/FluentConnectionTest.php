<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FluentConnectionTest extends Tester\TestCase
{
	/** @var Fluent\Connection */
	private $fluentConnection;


	protected function setUp(): void
	{
		parent::setUp();
		$this->fluentConnection = new Fluent\Connection();
	}


	public function testTable(): void
	{
		$query = $this->fluentConnection
			->table('table')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testSelect(): void
	{
		$query = $this->fluentConnection
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testDistinct(): void
	{
		$query = $this->fluentConnection
			->distinct()
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT DISTINCT 1', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFrom(): void
	{
		$query = $this->fluentConnection
			->from('table')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testJoin(): void
	{
		$query = $this->fluentConnection
			->join('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t INNER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInnerJoin(): void
	{
		$query = $this->fluentConnection
			->innerJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t INNER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testLeftJoin(): void
	{
		$query = $this->fluentConnection
			->leftJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t LEFT OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testLeftOuterJoin(): void
	{
		$query = $this->fluentConnection
			->leftOuterJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t LEFT OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testRightJoin(): void
	{
		$query = $this->fluentConnection
			->rightJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t RIGHT OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testRightOuterJoin(): void
	{
		$query = $this->fluentConnection
			->rightOuterJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t RIGHT OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFullJoin(): void
	{
		$query = $this->fluentConnection
			->fullJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t FULL OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testFullOuterJoin(): void
	{
		$query = $this->fluentConnection
			->fullOuterJoin('another_table', 'at', 'at.id = t.another_id')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t FULL OUTER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testCrossJoin(): void
	{
		$query = $this->fluentConnection
			->crossJoin('another_table', 'at')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t CROSS JOIN another_table AS at', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testOn(): void
	{
		$query = $this->fluentConnection
			->on('at', 'at.id = t.another_id')
			->innerJoin('another_table', 'at')
			->from('table', 't')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table AS t INNER JOIN another_table AS at ON at.id = t.another_id', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testWhere(): void
	{
		$query = $this->fluentConnection
			->where('TRUE')
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 WHERE TRUE', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testWhereAnd(): void
	{
		$query = $this->fluentConnection
			->whereAnd(['column = 1', 'another = 2'])
				->fluent()
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 WHERE column = 1 AND another = 2', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testWhereOr(): void
	{
		$query = $this->fluentConnection
			->whereOr(['column = 1', 'another = 2'])
				->fluent()
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 WHERE column = 1 OR another = 2', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testGroupBy(): void
	{
		$query = $this->fluentConnection
			->groupBy(['column'])
			->from('table')
			->select(['*'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table GROUP BY column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testHaving(): void
	{
		$query = $this->fluentConnection
			->having('column = 1')
			->select(['*'])
			->from('table')
			->groupBy(['column'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table GROUP BY column HAVING column = 1', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testHavingAnd(): void
	{
		$query = $this->fluentConnection
			->havingAnd(['column = 1', 'another = 2'])
				->fluent()
			->select(['*'])
			->from('table')
			->groupBy(['column', 'another'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table GROUP BY column, another HAVING column = 1 AND another = 2', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testHavingOr(): void
	{
		$query = $this->fluentConnection
			->havingOr(['column = 1', 'another = 2'])
				->fluent()
			->select(['*'])
			->from('table')
			->groupBy(['column', 'another'])
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table GROUP BY column, another HAVING column = 1 OR another = 2', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testOrderBy(): void
	{
		$query = $this->fluentConnection
			->orderBy(['column'])
			->select(['*'])
			->from('table')
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table ORDER BY column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testLimit(): void
	{
		$query = $this->fluentConnection
			->limit(100)
			->select(['*'])
			->from('table')
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table LIMIT $1', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testOffset(): void
	{
		$query = $this->fluentConnection
			->offset(100)
			->select(['*'])
			->from('table')
			->prepareSql();

		Tester\Assert::same('SELECT * FROM table OFFSET $1', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testUnion(): void
	{
		$query = $this->fluentConnection
			->union('SELECT 2')
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 UNION (SELECT 2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testUnionAll(): void
	{
		$query = $this->fluentConnection
			->unionAll('SELECT 2')
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 UNION ALL (SELECT 2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testIntersect(): void
	{
		$query = $this->fluentConnection
			->intersect('SELECT 2')
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 INTERSECT (SELECT 2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testExcept(): void
	{
		$query = $this->fluentConnection
			->except('SELECT 2')
			->select([1])
			->prepareSql();

		Tester\Assert::same('SELECT 1 EXCEPT (SELECT 2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());

	}


	public function testInsert(): void
	{
		$query = $this->fluentConnection
			->insert('table')
			->values(['column' => 1])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1)', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testValues(): void
	{
		$query = $this->fluentConnection
			->values(['column' => 1])
			->insert('table')
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1)', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testRows(): void
	{
		$query = $this->fluentConnection
			->rows([
				['column' => 1],
				['column' => 2],
			])
			->insert('table')
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1), ($2)', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testUpdate(): void
	{
		$query = $this->fluentConnection
			->update('table')
			->set(['column' => 1])
			->prepareSql();

		Tester\Assert::same('UPDATE table SET column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testSet(): void
	{
		$query = $this->fluentConnection
			->set(['column' => 1])
			->update('table')
			->prepareSql();

		Tester\Assert::same('UPDATE table SET column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testDelete(): void
	{
		$query = $this->fluentConnection
			->delete('table')
			->prepareSql();

		Tester\Assert::same('DELETE FROM table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testReturning(): void
	{
		$query = $this->fluentConnection
			->returning(['column'])
			->insert('table')
			->values(['column' => 1])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1) RETURNING column', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testTruncate(): void
	{
		$query = $this->fluentConnection
			->truncate('table')
			->prepareSql();

		Tester\Assert::same('TRUNCATE table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}

}

(new FluentConnectionTest())->run();
