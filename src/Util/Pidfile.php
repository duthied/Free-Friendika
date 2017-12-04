<?php
/**
 * @file src/Util/Pidfile.php
 */
namespace Friendica\Util;

/**
 * @brief Pidfile class
 */
class Pidfile
{
	private $file;
	private $running;

	/**
	 * @param string $dir  path
	 * @param string $name filename
	 * @return void
	 */
	public function __construct($dir, $name)
	{
		$this->_file = "$dir/$name.pid";

		if (file_exists($this->_file)) {
			$pid = trim(@file_get_contents($this->file));
			if (($pid != "") && posix_kill($pid, 0)) {
				$this->running = true;
			}
		}

		if (! $this->running) {
			$pid = getmypid();
			file_put_contents($this->file, $pid);
		}
	}

	/**
	 * @return void
	 */
	public function __destruct()
	{
		if ((! $this->running) && file_exists($this->file)) {
			@unlink($this->file);
		}
	}

	/**
	 * @return boolean
	 */
	public static function isRunning()
	{
		return self::$running;
	}

	/**
	 * @return object
	 */
	public static function runningTime()
	{
		return time() - @filectime(self::$file);
	}

	/**
	 * @return boolean
	 */
	public static function kill()
	{
		if (file_exists(self::$file)) {
			return posix_kill(file_get_contents(self::$file), SIGTERM);
		}
	}
}
