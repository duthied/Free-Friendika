<?php

namespace Friendica\Util;

/**
 * Util class for filesystem manipulation
 */
final class FileSystem
{
	/**
	 * @var string a error message
	 */
	private $errorMessage;

	/**
	 * Creates a directory based on a file, which gets accessed
	 *
	 * @param string $file The file
	 *
	 * @return string The directory name (empty if no directory is found, like urls)
	 */
	public function createDir(string $file)
	{
		$dirname = null;
		$pos = strpos($file, '://');

		if (!$pos) {
			$dirname = realpath(dirname($file));
		}

		if (substr($file, 0, 7) === 'file://') {
			$dirname = realpath(dirname(substr($file, 7)));
		}

		if (isset($dirname) && !is_dir($dirname)) {
			set_error_handler([$this, 'customErrorHandler']);
			$status = mkdir($dirname, 0777, true);
			restore_error_handler();

			if (!$status && !is_dir($dirname)) {
				throw new \UnexpectedValueException(sprintf('Directory "%s" cannot get created: ' . $this->errorMessage, $dirname));
			}

			return $dirname;
		} elseif (isset($dirname) && is_dir($dirname)) {
			return $dirname;
		} else {
			return '';
		}
	}

	/**
	 * Creates a stream based on a URL (could be a local file or a real URL)
	 *
	 * @param string $url The file/url
	 *
	 * @return false|resource the open stream ressource
	 */
	public function createStream(string $url)
	{
		$directory = $this->createDir($url);
		set_error_handler([$this, 'customErrorHandler']);
		if (!empty($directory)) {
			$url = $directory . DIRECTORY_SEPARATOR . pathinfo($url, PATHINFO_BASENAME);
		}

		$stream = fopen($url, 'ab');
		restore_error_handler();

		if (!is_resource($stream)) {
			throw new \UnexpectedValueException(sprintf('The stream or file "%s" could not be opened: ' . $this->errorMessage, $url));
		}

		return $stream;
	}

	private function customErrorHandler($code, $msg)
	{
		$this->errorMessage = preg_replace('{^(fopen|mkdir)\(.*?\): }', '', $msg);
	}
}
