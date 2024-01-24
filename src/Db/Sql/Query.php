<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Query implements Db\Sql
{
	private string $sql;

	/** @var list<mixed> */
	private array $params;

	private Db\Query|NULL $query = NULL;


	/**
	 * @param list<mixed> $params
	 */
	public function __construct(string $sql, array $params = [])
	{
		$this->sql = $sql;
		$this->params = $params;
	}


	public function getSql(): string
	{
		return $this->sql;
	}


	/**
	 * @return list<mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * Create SQL query for pg_query_params function.
	 */
	public function createQuery(): Db\Query
	{
		if ($this->query === NULL) {
			$this->query = self::prepareQuery($this->sql, $this->params, 0);
		}

		return $this->query;
	}


	/**
	 * @param list<mixed> $params
	 */
	private static function prepareQuery(string $sql, array $params, int $paramIndex): Db\Query
	{
		$origParamIndex = 0;
		$parsedParams = [];

		$sql = \preg_replace_callback(
			'/([\\\\]?)\?/',
			static function ($matches) use (&$params, &$parsedParams, &$origParamIndex, &$paramIndex): string {
				if ($matches[1] === '\\') {
					return '?';
				}

				if (!\array_key_exists($origParamIndex, $params)) {
					throw Db\Exceptions\QueryException::missingParam($origParamIndex);
				}

				$param = $params[$origParamIndex];
				unset($params[$origParamIndex]);
				$origParamIndex++;

				if (\is_array($param)) {
					$keys = [];
					foreach ($param as $value) {
						$keys[] = '$' . ++$paramIndex;
						$parsedParams[] = ($value instanceof \BackedEnum) ? $value->value : $value;
					}
					return \implode(', ', $keys);
				} else if (\is_bool($param)) {
					return $param === TRUE ? 'TRUE' : 'FALSE';
				} else if ($param instanceof Db\Sql) {
					$subquerySql = self::prepareQuery($param->getSql(), $param->getParams(), $paramIndex);
					$paramIndex += \count($subquerySql->getParams());
					$parsedParams = \array_merge($parsedParams, $subquerySql->getParams());
					return $subquerySql->getSql();
				}

				$parsedParams[] = ($param instanceof \BackedEnum) ? $param->value : $param;

				return '$' . ++$paramIndex;
			},
			$sql,
		);

		\assert(\is_string($sql));

		if (($origParamIndex > 0) && ($params !== [])) {
			throw Db\Exceptions\QueryException::extraParam(\array_values($params));
		}

		if ($parsedParams === []) {
			$parsedParams = \array_values($params);
		}

		return new Db\Query($sql, $parsedParams);
	}


	public static function create(string $sql, mixed ...$params): self
	{
		\assert(\array_is_list($params));
		return new self($sql, $params);
	}


	/**
	 * @param list<mixed> $params
	 */
	public static function createArgs(string $sql, array $params): self
	{
		return new self($sql, $params);
	}

}
