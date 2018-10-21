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
		if (!$array) {
			return '{}';
		}
		return sprintf('{\'%s\'}', \implode('\',\'', \array_map(function($value) {
			return \str_replace('\'', '\'\'', $value);
		}, $array)));
	}


	public static function createPgArray(array $array): string
	{
		if (!$array) {
			return '{}';
		}
		return sprintf('{%s}', \implode(',', $array));
	}


	/**
	 * @credit https://github.com/dg/dibi/blob/master/src/Dibi/Helpers.php
	 */
	public static function dump(string $sql, array $parameters = [])
	{
		static $keywords1 = 'SELECT|(?:ON\s+DUPLICATE\s+KEY)?UPDATE|INSERT(?:\s+INTO)?|REPLACE(?:\s+INTO)?|DELETE|CALL|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|FETCH\s+NEXT|SET|VALUES|LEFT\s+JOIN|INNER\s+JOIN|TRUNCATE|START\s+TRANSACTION|BEGIN|COMMIT|ROLLBACK(?:\s+TO\s+SAVEPOINT)?|(?:RELEASE\s+)?SAVEPOINT';
		static $keywords2 = 'ALL|DISTINCT|DISTINCTROW|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|RLIKE|REGEXP|TRUE|FALSE';

		// insert new lines
		$sql = " $sql ";
		$sql = \preg_replace("#(?<=[\\s,(])($keywords1)(?=[\\s,)])#i", "\n\$1", $sql);

		// reduce spaces
		$sql = \preg_replace('#[ \t]{2,}#', ' ', $sql);
		$sql = \wordwrap($sql, 100);
		$sql = \preg_replace("#([ \t]*\r?\n){2,}#", "\n", $sql);

		// syntax highlight
		$highlighter = "#(/\\*.+?\\*/)|(\\*\\*.+?\\*\\*)|(?<=[\\s,(])($keywords1)(?=[\\s,)])|(?<=[\\s,(=])($keywords2)(?=[\\s,)=])#is";
		if (PHP_SAPI === 'cli') {
			if (\substr((string) \getenv('TERM'), 0, 5) === 'xterm') {
				$sql = \preg_replace_callback($highlighter, function (array $m) {
					if (!empty($m[1])) { // comment
						return "\033[1;30m" . $m[1] . "\033[0m";
					} elseif (!empty($m[2])) { // error
						return "\033[1;31m" . $m[2] . "\033[0m";
					} elseif (!empty($m[3])) { // most important keywords
						return "\033[1;34m" . $m[3] . "\033[0m";
					} elseif (!empty($m[4])) { // other keywords
						return "\033[1;32m" . $m[4] . "\033[0m";
					}
				}, $sql);
			}
			$sql = \trim($sql);
		} else {
			$sql = \htmlspecialchars($sql);
			$sql = \preg_replace_callback($highlighter, function (array $m) {
				if (!empty($m[1])) { // comment
					return '<em style="color:gray">' . $m[1] . '</em>';
				} elseif (!empty($m[2])) { // error
					return '<strong style="color:red">' . $m[2] . '</strong>';
				} elseif (!empty($m[3])) { // most important keywords
					return '<strong style="color:blue">' . $m[3] . '</strong>';
				} elseif (!empty($m[4])) { // other keywords
					return '<strong style="color:green">' . $m[4] . '</strong>';
				}
			}, $sql);
			$sql = '<pre class="dump">' . \trim($sql) . '</pre>';
		}

		if ($parameters) {
			$sql = \preg_replace_callback(
				'/\$(\d+)/',
				function ($matches) use (& $parameters) {
					$i = $matches[1] - 1;

					if (isset($parameters[$i])) {
						$value = \str_replace('\'', '\'\'', $parameters[$i]);
						unset($parameters[$i]);
						return '\'' . $value . '\'';
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
			function ($matches) use (& $params, & $parsedParams, & $origParamIndex, & $paramIndex) {
				if ($matches[1] === '\\') {
					return '?';
				}

				if (!array_key_exists($origParamIndex, $params)) {
					throw Exceptions\QueryException::noParam($origParamIndex);
				}

				$param = $params[$origParamIndex];
				unset($params[$origParamIndex]);
				$origParamIndex++;

				if (\is_array($param)) {
					$keys = '';
					\array_walk($param, function() use (& $keys, & $paramIndex){
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
					$paramIndex += count($subqueryParams);
					$parsedParams = \array_merge($parsedParams, $subqueryParams);
					return $subquerySql;
				}

				$parsedParams[] = $param;

				return '$' . ++$paramIndex;
			},
			$sql
		);

		if (!$parsedParams) {
			$parsedParams = $params;
		}

		return [$sql, $parsedParams];
	}

}
