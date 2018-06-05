<?php declare(strict_types=1);

namespace Tests\Unit\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FluentTest extends Tester\TestCase
{

	public function testSelect()
	{
		$query = $this->fluent()
			->select(['column'])
			->distinct()
			->from('table', 't')
			->where('column', 100)
			->groupBy(['column'])
			->orderBy(['column'])
			->limit(10)
			->offset(20)
			->prepareSql();

		Tester\Assert::same('SELECT DISTINCT column FROM table AS t WHERE column = $1 GROUP BY column ORDER BY column LIMIT $2 OFFSET $3', $query->getSql());
		Tester\Assert::same([100, 10, 20], $query->getParams());
	}


	public function testSelectCombine()
	{
		$query = $this->fluent()
			->select(['column'])
			->from('table', 't')
			->union(
				$this->fluent()
					->select(['column'])
					->from('table2', 't2')
			)
			->prepareSql();

		Tester\Assert::same('SELECT column FROM table AS t UNION (SELECT column FROM table2 AS t2)', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testInsertRow()
	{
		$query = $this->fluent()
			->insert('table')
			->values([
				'column' => 1
			])
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1) RETURNING column', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testInsertRows()
	{
		$query = $this->fluent()
			->insert('table')
			->rows([
				['column' => 1],
				['column' => 2],
				['column' => 3],
			])
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) VALUES($1), ($2), ($3) RETURNING column', $query->getSql());
		Tester\Assert::same([1, 2, 3], $query->getParams());
	}


	public function testInsertSelect()
	{
		$query = $this->fluent()
			->insert('table')
			->select(['column'])
			->from('table2', 't2')
			->returning(['column'])
			->prepareSql();

		Tester\Assert::same('INSERT INTO table(column) SELECT column FROM table2 AS t2 RETURNING column', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testUpdate()
	{
		$query = $this->fluent()
			->update('table', 't')
			->set([
				'column' => 1,
				'column_from' => Db\Literal::create('t2.id')
			])
			->from('table2', 't2')
			->where('t2.column', 100)
			->returning(['t.column'])
			->prepareSql();

		Tester\Assert::same('UPDATE table AS t SET column = $1, column_from = t2.id FROM table2 AS t2 WHERE t2.column = $2 RETURNING t.column', $query->getSql());
		Tester\Assert::same([1, 100], $query->getParams());
	}


	public function testDelete()
	{
		$query = $this->fluent()
			->delete('table', 't')
			->where('column', 100)
			->returning(['c' => 'column'])
			->prepareSql();

		Tester\Assert::same('DELETE FROM table AS t WHERE column = $1 RETURNING column AS c', $query->getSql());
		Tester\Assert::same([100], $query->getParams());
	}


	public function testTruncate()
	{
		$query = $this->fluent()->truncate('table')->prepareSql();
		Tester\Assert::same('TRUNCATE table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	private function fluent()
	{
		return Fluent\Fluent::create();
	}

}

(new FluentTest)->run();
