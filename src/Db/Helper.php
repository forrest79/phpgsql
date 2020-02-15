<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Helper
{

	private function __construct()
	{
		// just static class
	}


	/**
	 * @param array<mixed> $array
	 */
	public static function createStringPgArray(array $array): string
	{
		if ($array === []) {
			return '{}';
		}
		foreach ($array as $i => $value) {
			$array[$i] = \str_replace('"', '\"', $value);
		}
		return '{"' . \implode('","', $array) . '"}';
	}


	/**
	 * @param array<mixed> $array
	 */
	public static function createPgArray(array $array): string
	{
		if ($array === []) {
			return '{}';
		}
		return '{' . \implode(',', $array) . '}';
	}


	/**
	 * @param array<int, mixed> $parameters
	 * @credit https://github.com/dg/dibi/blob/master/src/Dibi/Helpers.php
	 */
	public static function dump(string $sql, array $parameters = []): string
	{
		static $keywords1 = 'SELECT|INSERT(?:\s+INTO)?|DELETE|UNION|FROM|WHERE|HAVING|GROUP\s+BY|ORDER\s+BY|LIMIT|OFFSET|RETURNING|SET|VALUES|LEFT\s+(OUTER\s+)?JOIN|RIGHT\s+(OUTER\s+)?JOIN|INNER\s+JOIN|FULL\s+(OUTER\s+)?JOIN|CROSS\s+JOIN|TRUNCATE|BEGIN|COMMIT|ROLLBACK(?:\s+TO\s+SAVEPOINT)?|(?:RELEASE\s+)?SAVEPOINT';
		static $keywords2 = 'ALL|DISTINCT|IGNORE|AS|USING|ON|AND|OR|IN|IS|NOT|NULL|LIKE|ILIKE|TRUE|FALSE';

		// insert new lines
		$sql = ' ' . $sql . ' ';
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
		if (\PHP_SAPI === 'cli') {
			if (\substr((string) \getenv('TERM'), 0, 5) === 'xterm') {
				// intentionally (string), other can't be returned
				$sql = (string) \preg_replace_callback($highlighter, static function (array $m): string {
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
			// intentionally (string), other can't be returned
			$sql = (string) \preg_replace_callback($highlighter, static function (array $m): string {
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

		if ($parameters !== []) {
			$sql = (string) \preg_replace_callback( // intentionally (string), other can't be returned
				'/\$(\d+)/',
				static function ($matches) use (&$parameters): string {
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

}
