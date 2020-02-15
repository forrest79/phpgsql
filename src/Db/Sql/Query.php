<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Query implements Db\Sql
{
	/** @var string */
	private $sql;

	/** @var array<mixed> */
	private $params;

	/** @var Db\Query */
	private $query;


	/**
	 * @param array<mixed> $params
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
	 * @return array<mixed>
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
	 * @param array<mixed> $params
	 */
	private static function prepareQuery(string $sql, array $params, int $paramIndex): Db\Query
	{
		$origParamIndex = 0;
		$parsedParams = [];

		/** @var string $sql */
		$sql = \preg_replace_callback(
			'/([\\\\]?)\?/',
			static function ($matches) use (&$params, &$parsedParams, &$origParamIndex, &$paramIndex): string {
				if ($matches[1] === '\\') {
					return '?';
				}

				if (!\array_key_exists($origParamIndex, $params)) {
					throw Db\Exceptions\QueryException::noParam($origParamIndex);
				}

				$param = $params[$origParamIndex];
				unset($params[$origParamIndex]);
				$origParamIndex++;

				if (\is_array($param)) {
					$keys = [];
					$paramCnt = \count($param);
					for ($i = 0; $i < $paramCnt; $i++) {
						$keys[] = '$' . ++$paramIndex;
					}
					$parsedParams = \array_merge($parsedParams, $param);
					return \implode(', ', $keys);
				} else if (\is_bool($param)) {
					return $param === TRUE ? 'TRUE' : 'FALSE';
				} else if ($param instanceof Db\Sql) {
					$subquerySql = self::prepareQuery($param->getSql(), $param->getParams(), $paramIndex);
					$paramIndex += \count($subquerySql->getParams());
					$parsedParams = \array_merge($parsedParams, $subquerySql->getParams());
					return $subquerySql->getSql();
				}

				$parsedParams[] = $param;

				return '$' . ++$paramIndex;
			},
			$sql
		);

		if ($parsedParams === []) {
			$parsedParams = $params;
		}

		return new Db\Query($sql, $parsedParams);
	}


	/**
	 * @param mixed ...$params
	 * @return self
	 */
	public static function create(string $sql, ...$params): self
	{
		return new self($sql, $params);
	}


	/**
	 * @param array<mixed> $params
	 */
	public static function createArgs(string $sql, array $params): self
	{
		return new self($sql, $params);
	}

}
