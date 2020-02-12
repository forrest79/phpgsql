<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class FluentComplexTest extends Tester\TestCase
{

	public function testParent(): void
	{
		$parentComplex = Fluent\Complex::createAnd();

		Tester\Assert::same($parentComplex, $parentComplex->addComplexOr()->parent());
	}


	public function testNoParent(): void
	{
		Tester\Assert::exception(static function (): void {
			Fluent\Complex::createAnd()->parent();
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::NO_PARENT);
	}


	public function testFluent(): void
	{
		$query = new Fluent\Query(new Fluent\QueryBuilder());
		$parentComplex = Fluent\Complex::createAnd([], NULL, $query);

		Tester\Assert::same($query, $parentComplex->addComplexOr()->query());
	}


	public function testNoFluent(): void
	{
		Tester\Assert::exception(static function (): void {
			Fluent\Complex::createAnd()->query();
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::NO_QUERY);
	}


	public function testAddComplexWithParams(): void
	{
		Tester\Assert::exception(static function (): void {
			Fluent\Complex::createAnd()->add(Fluent\Complex::createAnd(), 'param1');
		}, Fluent\Exceptions\ComplexException::class, NULL, Fluent\Exceptions\ComplexException::ONLY_STRING_CONDITION_CAN_HAVE_PARAMS);
	}


	public function testAdd(): void
	{
		$complex = Fluent\Complex::createAnd();

		Tester\Assert::same(Fluent\Complex::TYPE_AND, $complex->getType());

		$complex->add('column = ?', 1);
		$complexAnd = $complex->addComplexAnd();
		$complexOr = $complex->addComplexOr();

		$conditions = $complex->getConditions();

		Tester\Assert::same(3, \count($conditions));
		Tester\Assert::same(['column = ?', 1], $conditions[0]);
		Tester\Assert::same($complexAnd, $conditions[1]);
		Tester\Assert::same($complexOr, $conditions[2]);
	}


	public function testArrayAcces(): void
	{
		$complex = Fluent\Complex::createOr();

		Tester\Assert::same(Fluent\Complex::TYPE_OR, $complex->getType());

		Tester\Assert::false(isset($complex[0]));
		Tester\Assert::null($complex[0]);

		$complex[] = ['column = ?', 1];

		Tester\Assert::true(isset($complex[0]));
		Tester\Assert::same(['column = ?', 1], $complex[0]);

		$complexAnd = Fluent\Complex::createAnd();
		$complexOr = Fluent\Complex::createOr();

		$complex[] = $complexAnd;

		Tester\Assert::same([$complexAnd], $complex[1]);

		$complex[1] = $complexOr;

		Tester\Assert::same([$complexOr], $complex[1]);

		unset($complex[1]);

		Tester\Assert::false(isset($complex[1]));
		Tester\Assert::null($complex[1]);

		$conditions = $complex->getConditions();

		Tester\Assert::same(1, \count($conditions));
		Tester\Assert::same([['column = ?', 1]], $conditions);
	}

}

\run(FluentComplexTest::class);
