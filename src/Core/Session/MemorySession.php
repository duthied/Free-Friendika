<?php

namespace Friendica\Core\Session;

/**
 * Native Session functions for internal Session usage.
 *
 * Usable for backend processes (daemon/worker) and testing
 */
final class MemorySession implements ISession
{
	private $data = [];

	public function start()
	{
		$this->clear();
		return $this;
	}

	/**
	 * @inheritDoc
	 */
	public function exists(string $name)
	{
		return isset($this->data[$name]);
	}

	/**
	 * @inheritDoc
	 */
	public function get(string $name, $defaults = null)
	{
		return $this->data[$name] ?? $defaults;
	}

	/**
	 * @inheritDoc
	 */
	public function set(string $name, $value)
	{
		$this->data[$name] = $value;
	}

	/**
	 * @inheritDoc
	 */
	public function setMultiple(array $values)
	{
		foreach ($values as $key => $value) {
			$this->data[$key] = $value;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function remove(string $name)
	{
		if ($this->exists($name)) {
			unset($this->data[$name]);
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function clear()
	{
		$this->data = [];
		return true;
	}

	/**
	 * @inheritDoc
	 */
	public function delete()
	{
		$this->data = [];
		return true;
	}
}