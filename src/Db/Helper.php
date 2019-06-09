<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Helper
{

	private function __construct()
	{
		// just static class
	}


	public static function createStringPgArray(array $array): string
	{
		if (\count($array) === 0) {
			return '{}';
		}
		return \sprintf('{\'%s\'}', \implode('\',\'', \array_map(static function ($value): string {
			return \str_replace('\'', '\'\'', $value);
		}, $array)));
	}


	public static function createPgArray(array $array): string
	{
		if (\count($array) === 0) {
			return '{}';
		}
		return \sprintf('{%s}', \implode(',', $array));
	}


	/**
	 * @credit https://github.com/dg/dibi/blob/master/src/Dibi/Helpers.php
	 */
	public static function dump(string $sql, array $parameters = []): string
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|FETCH\s+NEXT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE|START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK(?:\s+TO\s+SAVEPOINT)?|(?:RELEASE\s+)?SAVEPOINT';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

		// insert new lines
		$sql = \sprintf(' %s ', $sql);
		$sql = (string) \preg_replace(\sprintf('#(?<=[\\s,(])(%s)(?=[\\s,)])#i', $keywords1), "\n\$1", $sql); // intentionally (string), other can't be returned

		// reduce spaces
		$sql = (string) \preg_replace('#[ \t]{2,}#', ' ', $sql); // intentionally (string), other can't be returned
		$sql = \wordwrap($sql, 100);
		$sql = (string) \preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql); // intentionally (string), other can't be returned

		// syntax highlight
		$highlighter = \sprintf(
			'#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])(%s)(?=[\\s,)])|(?<=[\\s,(=])(%s)(?=[\\s,)=])#is',
			$keywords1,
			$keywords2
		);
		if (PHP_SAPI === 'cli') {
			if (\substr((string) \getenv('TERM'), 0, 5) === 'xterm') {
				$sql = (string) \preg_replace_callback($highlighter, static function (array $m): string { // intentionally (string), other can't be returned
					if (isset($m[1]) && $m[1]) { // comment
						return \sprintf("\033[1;30m%s\033[0m", $m[1]);
					} elseif (isset($m[2]) && $m[2]) { // error
						return \sprintf("\033[1;31m%s\033[0m", $m[2]);
					} elseif (isset($m[3]) && $m[3]) { // most important keywords
						return \sprintf("\033[1;34m%s\033[0m", $m[3]);
					} elseif (isset($m[4]) && $m[4]) { // other keywords
						return \sprintf("\033[1;32m%s\033[0m", $m[4]);
					}
					return $m[0];
				}, $sql);
			}
			$sql = \trim($sql);
		} else {
			$sql = \htmlspecialchars($sql);
			$sql = (string) \preg_replace_callback($highlighter, static function (array $m): string { // intentionally (string), other can't be returned
				if (isset($m[1]) && $m[1]) { // comment
					return \sprintf('<em style="color:gray">%s</em>', $m[1]);
				} elseif (isset($m[2]) && $m[2]) { // error
					return \sprintf('<strong style="color:red">%s</strong>', $m[2]);
				} elseif (isset($m[3]) && $m[3]) { // most important keywords
					return \sprintf('<strong style="color:blue">%s</strong>', $m[3]);
				} elseif (isset($m[4]) && $m[4]) { // other keywords
					return \sprintf('<strong style="color:green">%s</strong>', $m[4]);
				}
				return $m[0];
			}, $sql);
			$sql = \sprintf('<pre class="dump">%s</pre>', \trim($sql));
		}

		if (\count($parameters) > 0) {
			$sql = (string) \preg_replace_callback( // intentionally (string), other can't be returned
				'/\$(\d+)/',
				static function ($matches) use (& $parameters): string {
					$i = $matches[1] - 1;

					if (\array_key_exists($i, $parameters)) {
						$value = $parameters[$i];
						unset($parameters[$i]);
						return ($value === NULL) ? 'NULL' : \sprintf('\'%s\'', \str_replace('\'', '\'\'', $value));
					}

					return $matches[0];
				},
				$sql
			);
		}

		return $sql;
	}


	/**
	 * Create SQL query for pg_query_params function.
	 */
	public static function prepareSql(Query $query): Query
	{
		[$sql, $params] = self::createSql($query->getSql(), $query->getParams(), 0);
		return new Query($sql, $params);
	}


	private static function createSql(string $sql, array $params, int $paramIndex): array
	{
		$origParamIndex = 0;
		$parsedParams = [];
		$sql = \preg_replace_callback(
			'/([\\\\]?)\?/',
			static function ($matches) use (& $params, & $parsedParams, & $origParamIndex, & $paramIndex): string {
				if ($matches[1] === '\\') {
					return '?';
				}

				if (!\array_key_exists($origParamIndex, $params)) {
					throw Exceptions\QueryException::noParam($origParamIndex);
				}

				$param = $params[$origParamIndex];
				unset($params[$origParamIndex]);
				$origParamIndex++;

				if (\is_array($param)) {
					$keys = '';
					\array_walk($param, static function () use (& $keys, & $paramIndex): void {
						$keys .= '$' . ++$paramIndex . ', ';
					});
					$parsedParams = \array_merge($parsedParams, $param);
					return \substr($keys, 0, -2);
				} else if (\is_bool($param)) {
					return $param === TRUE ? 'TRUE' : 'FALSE';
				} else if ($param instanceof Literal) {
					return (string) $param;
				} else if ($param instanceof Query) {
					[$subquerySql, $subqueryParams] = self::createSql($param->getSql(), $param->getParams(), $paramIndex);
					$paramIndex += \count($subqueryParams);
					$parsedParams = \array_merge($parsedParams, $subqueryParams);
					return $subquerySql;
				}

				$parsedParams[] = $param;

				return '$' . ++$paramIndex;
			},
			$sql
		);

		if ($parsedParams === []) {
			$parsedParams = $params;
		}

		return [$sql, $parsedParams];
	}

}
