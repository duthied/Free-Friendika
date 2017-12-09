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
	private $pid;

	/**
	 * @param string $dir  path
	 * @param string $name filename
	 * @return void
	 */
	public function __construct($dir, $name)
	{
		$this->file = "$dir/$name";
		$this->running = false;

		if (file_exists($this->file)) {
			$this->pid = trim(@file_get_contents($this->file));
			if (($this->pid != "") && posix_kill($this->pid, 0)) {
				$this->running = true;
			}
		}

		if (!$this->running) {
			$this->pid = getmypid();
			file_put_contents($this->file, $this->pid);
		}
	}

	/**
	 * @return void
	 */
	public function __destruct()
	{
		if (!$this->running && file_exists($this->file)) {
			@unlink($this->file);
		}
	}

	/**
	 * @brief Check if a process with this pid file is already running
	 * @return boolean Is it running?
	 */
	public function isRunning()
	{
		return $this->running;
	}

	/**
	 * @brief Return the pid of the process
	 * @return boolean process id
	 */
	public function pid()
	{
		return $this->pid;
	}

	/**
	 * @brief Returns the seconds that the old process was running
	 * @return integer run time of the old process
	 */
	public function runningTime()
	{
		return time() - @filectime($this->file);
	}

	/**
	 * @brief Kills the old process
	 * @return boolean
	 */
	public function kill()
	{
		if (!empty($this->pid)) {
			return posix_kill($this->pid, SIGTERM);
		}
	}
}
