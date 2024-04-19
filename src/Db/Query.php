<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

final class Query
{

	/**
	 * @param list<mixed> $params
	 */
	public function __construct(public readonly string $sql, public readonly array $params)
	{
	}


	/**
	 * @param list<mixed> $params
	 */
	public static function from(string|self|Sql $query, array $params = []): self
	{
		if (is_string($query)) {
			$query = new Sql\Expression($query, $params);
		} else if ($params !== []) {
			throw Exceptions\QueryException::cantPassParams();
		}

		return $query instanceof Sql ? self::prepareQuery($query->getSql(), $query->getParams(), 0) : $query;
	}


	/**
	 * @param list<mixed> $params
	 */
	private static function prepareQuery(string $sql, array $params, int $paramIndex): self
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
					throw Exceptions\QueryException::missingParam($origParamIndex);
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
					return $param === TRUE ? 'TRUE' : 'FALSE'; // @todo as param
				} else if ($param instanceof Sql) {
					$subquerySql = self::prepareQuery($param->getSql(), $param->getParams(), $paramIndex);
					$paramIndex += \count($subquerySql->params);
					$parsedParams = \array_merge($parsedParams, $subquerySql->params);
					return $subquerySql->sql;
				}

				$parsedParams[] = ($param instanceof \BackedEnum) ? $param->value : $param;

				return '$' . ++$paramIndex;
			},
			$sql,
		);

		\assert(\is_string($sql));

		if (($origParamIndex > 0) && ($params !== [])) {
			throw Exceptions\QueryException::extraParam(\array_values($params));
		}

		if ($parsedParams === []) {
			$parsedParams = \array_values($params);
		}

		return new self($sql, $parsedParams);
	}

}
