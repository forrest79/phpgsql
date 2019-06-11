<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeCaches;

use Forrest79\PhPgSql\Db;

class PhpFile extends DbLoader
{
	/** @var array|NULL */
	private $cache;

	/** @var string */
	private $cacheFile;


	public function __construct(string $cacheFile)
	{
		$this->cacheFile = $cacheFile;
	}


	public function load(Db\Connection $connection): array
	{
		if ($this->cache === NULL) {
			if (!\is_file($this->cacheFile)) {
				$cacheDir = \dirname($this->cacheFile);
				if (!\is_dir($cacheDir)) {
					\mkdir($cacheDir, 0777, TRUE);
				}

				$lockFile = $this->cacheFile . '.lock';
				$handle = \fopen($lockFile, 'c+');
				if (($handle === FALSE) || !\flock($handle, \LOCK_EX)) {
					throw new \RuntimeException(\sprintf('Unable to create or acquire exclusive lock on file \'%s\'.', $lockFile));
				}

				// cache still not exists
				if (!\is_file($this->cacheFile)) {
					$tempFile = $this->cacheFile . '.tmp';
					\file_put_contents(
						$tempFile,
						'<?php declare(strict_types=1);' . \PHP_EOL . \sprintf('return [%s];', self::prepareCacheArray($this->loadFromDb($connection)))
					);
					\rename($tempFile, $this->cacheFile); // atomic replace (in Linux)

					if (\function_exists('opcache_invalidate')) {
						\opcache_invalidate($this->cacheFile, TRUE);
					}
				}

				\flock($handle, \LOCK_UN);
				\fclose($handle);
				@\unlink($lockFile); // intentionally @ - file may become locked on Windows
			}

			$this->cache = require $this->cacheFile;
		}

		return $this->cache;
	}


	private static function prepareCacheArray(array $data): string
	{
		$cache = '';
		\array_walk($data, static function (string $typname, int $oid) use (&$cache): void {
			$cache .= \sprintf("%d=>'%s',", $oid, \str_replace("'", "\\'", $typname));
		});
		return $cache;
	}


	public function clean(): self
	{
		@\unlink($this->cacheFile); // intentionally @ - file may not exists
		$this->cache = NULL;
		return $this;
	}

}
