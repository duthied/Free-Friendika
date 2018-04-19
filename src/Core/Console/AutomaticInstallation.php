<?php

namespace Friendica\Core\Console;

use Asika\SimpleConsole\Console;
use dba;
use Friendica\App;

require_once 'mod/install.php';
require_once 'include/dba.php';

class AutomaticInstallation extends Console
{
	protected function getHelp()
	{
		return <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a] 
	
Description
    Installs Friendica with data based on the htconfig.php file

Notes:
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
    -a           All setup checks are required (except .htaccess)
HELP;
	}

	protected function doExecute()
	{
		// Initialise the app
		$this->out("Initializing setup...\n");

		$a = get_app();
		$db_host = '';
		$db_user = '';
		$db_pass = '';
		$db_data = '';
		require_once 'htconfig.php';

		$this->out(" Complete!\n\n");

		// Check basic setup
		$this->out("Checking basic setup...\n");

		$checkResults = [];
		$checkResults['basic'] = $this->runBasicChecks($a);
		$errorMessage = $this->extractErrors($checkResults['basic']);

		if ($errorMessage !== '') {
			throw new \RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Check database connection
		$this->out("Checking database...\n");

		$checkResults['db'] = array();
		$checkResults['db'][] = $this->runDatabaseCheck($db_host, $db_user, $db_pass, $db_data);
		$errorMessage = $this->extractErrors($checkResults['db']);

		if ($errorMessage !== '') {
			throw new \RuntimeException($errorMessage);
		}

		$this->out(" Complete!\n\n");

		// Install database
		$this->out("Inserting data into database...\n");

		$checkResults['data'] = load_database();

		if ($checkResults['data'] !== '') {
			throw new \RuntimeException("ERROR: DB Database creation error. Is the DB empty?\n");
		}

		$this->out(" Complete!\n\n");

		// Copy config file
		$this->out("Saving config file...\n");
		if (!copy('htconfig.php', '.htconfig.php')) {
			throw new \RuntimeException("ERROR: Saving config file failed. Please copy .htautoinstall.php to .htconfig.php manually.\n");
		}
		$this->out(" Complete!\n\n");
		$this->out("\nInstallation is finished\n");

		return 0;
	}

	/**
	 * @param App $app
	 * @return array
	 */
	private function runBasicChecks($app)
	{
		$checks = [];

		check_funcs($checks);
		check_imagik($checks);
		check_htconfig($checks);
		check_smarty3($checks);
		check_keys($checks);

		if (!empty($app->config['php_path'])) {
			check_php($app->config['php_path'], $checks);
		} else {
			throw new \RuntimeException(" ERROR: The php_path is not set in the config. Please check the file .htconfig.php.\n");
		}

		$this->out(" NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.\n");

		return $checks;
	}

	/**
	 * @param $db_host
	 * @param $db_user
	 * @param $db_pass
	 * @param $db_data
	 * @return array
	 */
	private function runDatabaseCheck($db_host, $db_user, $db_pass, $db_data)
	{
		$result = array(
			'title' => 'MySQL Connection',
			'required' => true,
			'status' => true,
			'help' => '',
		);


		if (!dba::connect($db_host, $db_user, $db_pass, $db_data, true)) {
			$result['status'] = false;
			$result['help'] = 'Failed, please check your MySQL settings and credentials.';
		}

		return $result;
	}

	/**
	 * @param array $results
	 * @return string
	 */
	private function extractErrors($results)
	{
		$errorMessage = '';
		$allChecksRequired = $this->getOption('a') !== null;

		foreach ($results as $result) {
			if (($allChecksRequired || $result['required'] === true) && $result['status'] === false) {
				$errorMessage .= "--------\n";
				$errorMessage .= $result['title'] . ': ' . $result['help'] . "\n";
			}
		}

		return $errorMessage;
	}
}
