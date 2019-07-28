<?php

namespace Friendica\Test\Util\Database;

use Friendica\Database\Database;
use Symfony\Component\Yaml\Yaml;

/**
 * Util class to load YAML files into the database
 */
class YamlDataSet
{
	/**
	 * @var array
	 */
	private $tables = [];

	public function __construct(string $yamlFile)
	{
		$this->addYamlFile($yamlFile);
	}

	public function addYamlFile(string $yamlFile)
	{
		$data = Yaml::parse(file_get_contents($yamlFile));

		foreach ($data as $tableName => $rows) {
			if (!isset($rows)) {
				$rows = [];
			}

			if (!is_array($rows)) {
				continue;
			}

			foreach ($rows as $key => $value) {
				$this->tables[$tableName][$key] = $value;
			}
		}
	}

	public function load(Database $database)
	{
		foreach ($this->tables as $tableName => $rows) {
			foreach ($rows as $row) {
				$database->insert($tableName, $row);
			}
		}
	}
}
