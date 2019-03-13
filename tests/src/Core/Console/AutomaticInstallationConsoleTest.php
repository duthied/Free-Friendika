<?php

namespace Friendica\Test\src\Core\Console;

use Friendica\Core\Config\Cache\ConfigCache;
use Friendica\Core\Console\AutomaticInstallation;
use Friendica\Core\Installer;
use Friendica\Core\Logger;
use Friendica\Test\Util\DBAMockTrait;
use Friendica\Test\Util\DBStructureMockTrait;
use Friendica\Test\Util\L10nMockTrait;
use Friendica\Test\Util\RendererMockTrait;
use Friendica\Util\Logger\VoidLogger;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamFile;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 * @requires PHP 7.0
 */
class AutomaticInstallationConsoleTest extends ConsoleTest
{
	use L10nMockTrait;
	use DBAMockTrait;
	use DBStructureMockTrait;
	use RendererMockTrait;

	private $db_host;
	private $db_port;
	private $db_data;
	private $db_user;
	private $db_pass;

	/**
	 * @var vfsStreamFile Assert file without DB credentials
	 */
	private $assertFile;
	/**
	 * @var vfsStreamFile Assert file with DB credentials
	 */
	private $assertFileDb;

	public function setUp()
	{
		parent::setUp();

		if ($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php')) {
			$this->root->getChild('config')
				->removeChild('local.config.php');
		}

		$this->db_host = getenv('MYSQL_HOST');
		$this->db_port = !empty(getenv('MYSQL_PORT')) ? getenv('MYSQL_PORT') : null;
		$this->db_data = getenv('MYSQL_DATABASE');
		$this->db_user = getenv('MYSQL_USERNAME') . getenv('MYSQL_USER');
		$this->db_pass = getenv('MYSQL_PASSWORD');

		$this->mockL10nT();
	}

	/**
	 * Returns the dataset for each automatic installation test
	 *
	 * @return array the dataset
	 */
	public function dataInstaller()
	{
		return [
			'empty' => [
				'data' => [
					'database' => [
						'hostname'    => '',
						'username'    => '',
						'password'    => '',
						'database'    => '',
						'port'        => '',
					],
					'config' => [
						'php_path'    => '',
						'hostname'    => '',
						'admin_email' => '',
					],
					'system' => [
						'urlpath'     => '',
						'url'         => '',
						'basepath'    => '',
						'ssl_policy'  => '',
						'default_timezone' => '',
						'language'    => '',
					],
				],
			],
			'normal' => [
				'data' => [
					'database' => [
						'hostname'    => getenv('MYSQL_HOST'),
						'port'        =>!empty(getenv('MYSQL_PORT')) ? getenv('MYSQL_PORT') : null,
						'username'    => getenv('MYSQL_USERNAME'),
						'password'    => getenv('MYSQL_PASSWORD'),
						'database'    => getenv('MYSQL_DATABASE'),
					],
					'config' => [
						'php_path'    => '',
						'hostname'    => 'friendica.local',
						'admin_email' => 'admin@philipp.info',
					],
					'system' => [
						'urlpath'     => 'test/it',
						'url'         => 'friendica.local/test/it',
						'basepath'    => '',
						'ssl_policy'  => '2',
						'default_timezone' => 'en',
						'language'    => 'Europe/Berlin',
					],
				],
			],
		];
	}

	private function assertFinished($txt, $withconfig = false, $copyfile = false)
	{
		$cfg = '';

		if ($withconfig) {
			$cfg = <<<CFG


Creating config file...

 Complete!
CFG;
		}

		if ($copyfile) {
			$cfg = <<<CFG


Copying config file...

 Complete!
CFG;
		}

		$finished = <<<FIN
Initializing setup...

 Complete!


Checking environment...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!
{$cfg}


Checking database...

 Complete!


Inserting data into database...

 Complete!


Installing theme

 Complete



Installation is finished


FIN;
		$this->assertEquals($finished, $txt);
	}

	private function assertStuckDB($txt)
	{
		$finished = <<<FIN
Initializing setup...

 Complete!


Checking environment...

 NOTICE: Not checking .htaccess/URL-Rewrite during CLI installation.

 Complete!


Creating config file...

 Complete!


Checking database...

[Error] --------
Could not connect to database.: 


FIN;

		$this->assertEquals($finished, $txt);
	}

	/**
	 * Test the automatic installation without any parameter/setting
	 */
	public function testEmpty()
	{
		$configCache = new ConfigCache();
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, null, true);

		$this->configMock->shouldReceive('set')->andReturnUsing(function ($cat, $key, $value) use ($configCache) {
			if ($key !== 'basepath') {
				return $configCache->set($cat, $key, $value);
			} else {
				return true;
			}
		});;
		$this->configMock->shouldReceive('has')->andReturn(true);
		$this->configMock->shouldReceive('get')->andReturnUsing(function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		});

		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([$this->root->url(), false, true, true], null, 1);

		$this->mockGetMarkupTemplate('local.config.tpl', 'testTemplate', 1);
		$this->mockReplaceMacros('testTemplate', \Mockery::any(), '', 1);

		$console = new AutomaticInstallation($this->consoleArgv);

		$txt = $this->dumpExecute($console);

		$this->assertFinished($txt, true, false);
		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php'));

		// Assert the default values without any config
		$this->assertEquals(Installer::DEFAULT_HOST, $configCache->get('database', 'hostname'));
		$this->assertEmpty($configCache->get('database', 'username'));
		$this->assertEmpty($configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'database'));

		$this->assertEmpty($configCache->get('config', 'hostname'), $configCache->get('config', 'hostname'));
		$this->assertEmpty($configCache->get('config', 'admin_email'), $configCache->get('config', 'admin_email'));
		$this->assertEquals(trim(shell_exec('which php')), $configCache->get('config', 'php_path'));

		$this->assertEquals(Installer::DEFAULT_TZ, $configCache->get('system', 'default_timezone'));
		$this->assertEquals(Installer::DEFAULT_LANG, $configCache->get('system', 'language'));
		$this->assertEquals(SSL_POLICY_NONE, $configCache->get('system', 'ssl_policy'));
		$this->assertEmpty($configCache->get('system', 'urlpath'), $configCache->get('system', 'urlpath'));
		$this->assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	/**
	 * Test the automatic installation with a prepared config file
	 * @dataProvider dataInstaller
	 */
	public function testWithConfig(array $data)
	{
		$configCache = new ConfigCache();
		$configCache->load($data);
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, $configCache, true);
		$this->mode->shouldReceive('isInstall')->andReturn(false);
		Logger::init(new VoidLogger());

		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([$this->root->url(), false, true, true], null, 1);

		$conf = function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		};

		$config = <<<CONF
<?php

// Local configuration

// If you're unsure about what any of the config keys below do, please check the config/defaults.config.php for detailed
// documentation of their data type and behavior.

return [
	'database' => [
		'hostname' => '{$conf('database','hostname')}',
		'username' => '{$conf('database', 'username')}',
		'password' => '{$conf('database', 'password')}',
		'database' => '{$conf('database', 'database')}',
		'charset' => 'utf8mb4',
	],

	// ****************************************************************
	// The configuration below will be overruled by the admin panel.
	// Changes made below will only have an effect if the database does
	// not contain any configuration for the friendica system.
	// ****************************************************************

	'config' => [
		'admin_email' => '{$conf('config', 'admin_email')}',
		'hostname' => '{$conf('config', 'hostname')}',
		'sitename' => 'Friendica Social Network',
		'register_policy' => \Friendica\Module\Register::OPEN,
		'register_text' => '',
	],
	'system' => [
		'basepath => '{$conf('system', 'basepath')}',
		'urlpath => '{$conf('system', 'urlpath')}',
		'url' => '{$conf('system', 'url')}',
		'default_timezone' => '{$conf('system', 'default_timezone')}',
		'language' => '{$conf('system', 'language')}',
	],
];
CONF;

		vfsStream::newFile('prepared.config.php')
			->at($this->root)
			->setContent($config);

		$console = new AutomaticInstallation($this->consoleArgv);
		$console->setOption('f', 'prepared.config.php');

		$txt = $this->dumpExecute($console);

		$this->assertFinished($txt, false, true);

		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php'));
		$this->assertEquals($config, file_get_contents($this->root->getChild('config' . DIRECTORY_SEPARATOR . 'local.config.php')->url()));
	}

	/**
	 * Test the automatic installation with environment variables
	 * Includes saving the DB credentials to the file
	 * @dataProvider dataInstaller
	 */
	public function testWithEnvironmentAndSave(array $data)
	{
		$configCache = new ConfigCache();
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, null, true);

		$this->configMock->shouldReceive('set')->andReturnUsing(function ($cat, $key, $value) use ($configCache) {
			if ($key !== 'basepath') {
				return $configCache->set($cat, $key, $value);
			} else {
				return true;
			}
		});;
		$this->configMock->shouldReceive('has')->andReturn(true);
		$this->configMock->shouldReceive('get')->andReturnUsing(function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		});

		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([$this->root->url(), false, true, true], null, 1);

		$this->mockGetMarkupTemplate('local.config.tpl', 'testTemplate', 1);
		$this->mockReplaceMacros('testTemplate', \Mockery::any(), '', 1);

		$this->assertTrue(putenv('MYSQL_HOST='     . $data['database']['hostname']));
		$this->assertTrue(putenv('MYSQL_PORT='     . $data['database']['port']));
		$this->assertTrue(putenv('MYSQL_DATABASE=' . $data['database']['database']));
		$this->assertTrue(putenv('MYSQL_USERNAME=' . $data['database']['username']));
		$this->assertTrue(putenv('MYSQL_PASSWORD=' . $data['database']['password']));

		$this->assertTrue(putenv('FRIENDICA_URL_PATH='   . $data['system']['urlpath']));
		$this->assertTrue(putenv('FRIENDICA_BASE_PATH='  . $data['system']['basepath']));
		$this->assertTrue(putenv('FRIENDICA_PHP_PATH='   . $data['config']['php_path']));
		$this->assertTrue(putenv('FRIENDICA_SSL_POLICY=' . $data['system']['ssl_policy']));
		$this->assertTrue(putenv('FRIENDICA_HOSTNAME='   . $data['config']['hostname']));
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=' . $data['config']['admin_email']));
		$this->assertTrue(putenv('FRIENDICA_TZ='         . $data['system']['default_timezone']));
		$this->assertTrue(putenv('FRIENDICA_LANG='       . $data['system']['language']));

		$console = new AutomaticInstallation($this->consoleArgv);
		$console->setOption('savedb', true);

		$txt = $this->dumpExecute($console);

		// Assert the default values without any config
		$dbhost = $data['database']['hostname'] . (!empty($data['database']['port']) ? ':' . $data['database']['port'] : '');
		$this->assertEquals($dbhost, $configCache->get('database', 'hostname'));
		$this->assertEquals($data['database']['username'], $configCache->get('database', 'username'));
		$this->assertEquals($data['database']['database'], $configCache->get('database', 'database'));
		$this->assertEquals($data['database']['password'], $configCache->get('database', 'password'));

		$this->assertEquals($data['config']['hostname'], $configCache->get('config', 'hostname'));
		$this->assertEquals($data['config']['admin_email'], $configCache->get('config', 'admin_email'));
		$this->assertEquals(trim(shell_exec('which php')), $configCache->get('config', 'php_path'));

		$this->assertEquals((!empty($data['system']['default_timezone']) ? $data['system']['default_timezone'] : Installer::DEFAULT_TZ),
			$configCache->get('system', 'default_timezone'));
		$this->assertEquals((!empty($data['system']['language']) ? $data['system']['language'] : Installer::DEFAULT_LANG),
			$configCache->get('system', 'language'));
		$this->assertEquals((!empty($data['system']['ssl_policy']) ? $data['system']['ssl_policy'] : SSL_POLICY_NONE),
			$configCache->get('system', 'ssl_policy'));
		$this->assertEquals((!empty($data['system']['urlpath']) ? $data['system']['urlpath'] : null),
			$configCache->get('system', 'urlpath'));

		$this->assertFinished($txt, true);
	}

	/**
	 * Test the automatic installation with environment variables
	 * Don't save the db credentials to the file
	 * @dataProvider dataInstaller
	 */
	public function testWithEnvironmentWithoutSave(array $data)
	{
		$configCache = new ConfigCache();
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, null, true);

		$this->configMock->shouldReceive('set')->andReturnUsing(function ($cat, $key, $value) use ($configCache) {
			if ($key !== 'basepath') {
				return $configCache->set($cat, $key, $value);
			} else {
				return true;
			}
		});;
		$this->configMock->shouldReceive('has')->andReturn(true);
		$this->configMock->shouldReceive('get')->andReturnUsing(function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		});

		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([$this->root->url(), false, true, true], null, 1);

		$this->mockGetMarkupTemplate('local.config.tpl', 'testTemplate', 1);
		$this->mockReplaceMacros('testTemplate', \Mockery::any(), '', 1);

		$this->assertTrue(putenv('MYSQL_HOST=' . $data['database']['hostname']));
		$this->assertTrue(putenv('MYSQL_PORT=' . $data['database']['port']));
		$this->assertTrue(putenv('MYSQL_DATABASE=' . $data['database']['database']));
		$this->assertTrue(putenv('MYSQL_USERNAME=' . $data['database']['username']));
		$this->assertTrue(putenv('MYSQL_PASSWORD=' . $data['database']['password']));

		$this->assertTrue(putenv('FRIENDICA_URL_PATH=' . $data['system']['urlpath']));
		$this->assertTrue(putenv('FRIENDICA_BASE_PATH=' . $data['system']['basepath']));
		$this->assertTrue(putenv('FRIENDICA_PHP_PATH=' . $data['config']['php_path']));
		$this->assertTrue(putenv('FRIENDICA_SSL_POLICY=' . $data['system']['ssl_policy']));
		$this->assertTrue(putenv('FRIENDICA_HOSTNAME=' . $data['config']['hostname']));
		$this->assertTrue(putenv('FRIENDICA_ADMIN_MAIL=' . $data['config']['admin_email']));
		$this->assertTrue(putenv('FRIENDICA_TZ=' . $data['system']['default_timezone']));
		$this->assertTrue(putenv('FRIENDICA_LANG=' . $data['system']['language']));

		$console = new AutomaticInstallation($this->consoleArgv);

		$txt = $this->dumpExecute($console);

		$this->assertEquals(Installer::DEFAULT_HOST, $configCache->get('database', 'hostname'));
		$this->assertEmpty($configCache->get('database', 'username'), $configCache->get('database', 'username'));
		$this->assertEmpty($configCache->get('database', 'password'), $configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'database'), $configCache->get('database', 'database'));

		$this->assertEquals($data['config']['hostname'], $configCache->get('config', 'hostname'));
		$this->assertEquals($data['config']['admin_email'], $configCache->get('config', 'admin_email'));
		$this->assertEquals(trim(shell_exec('which php')), $configCache->get('config', 'php_path'));

		$this->assertEquals((!empty($data['system']['default_timezone']) ? $data['system']['default_timezone'] : Installer::DEFAULT_TZ),
			$configCache->get('system', 'default_timezone'));
		$this->assertEquals((!empty($data['system']['language']) ? $data['system']['language'] : Installer::DEFAULT_LANG),
			$configCache->get('system', 'language'));
		$this->assertEquals((!empty($data['system']['ssl_policy']) ? $data['system']['ssl_policy'] : SSL_POLICY_NONE),
			$configCache->get('system', 'ssl_policy'));
		$this->assertEquals((!empty($data['system']['urlpath']) ? $data['system']['urlpath'] : null),
			$configCache->get('system', 'urlpath'));

		$this->assertFinished($txt, true);
	}

	/**
	 * Test the automatic installation with arguments
	 * @dataProvider dataInstaller
	 */
	public function testWithArguments(array $data)
	{
		$configCache = new ConfigCache();
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, null, true);

		$this->configMock->shouldReceive('set')->andReturnUsing(function ($cat, $key, $value) use ($configCache) {
			if ($key !== 'basepath') {
				return $configCache->set($cat, $key, $value);
			} else {
				return true;
			}
		});;
		$this->configMock->shouldReceive('has')->andReturn(true);
		$this->configMock->shouldReceive('get')->andReturnUsing(function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		});

		$this->mockConnect(true, 1);
		$this->mockConnected(true, 1);
		$this->mockExistsTable('user', false, 1);
		$this->mockUpdate([$this->root->url(), false, true, true], null, 1);

		$this->mockGetMarkupTemplate('local.config.tpl', 'testTemplate', 1);
		$this->mockReplaceMacros('testTemplate', \Mockery::any(), '', 1);

		$console = new AutomaticInstallation($this->consoleArgv);

		if (!empty($data['database']['hostname'])) {
			$console->setOption('dbhost', $data['database']['hostname']);
		}
		if (!empty($data['database']['port'])) {
			$console->setOption('dbport', $data['database']['port']);
		}
		if (!empty($data['database']['username'])) {
			$console->setOption('dbuser', $data['database']['username']);
		}
		if (!empty($data['database']['password'])) {
			$console->setOption('dbpass', $data['database']['password']);
		}
		if (!empty($data['database']['database'])) {
			$console->setOption('dbdata', $data['database']['database']);
		}
		if (!empty($data['system']['urlpath'])) {
			$console->setOption('urlpath', $data['system']['urlpath']);
		}
		if (!empty($data['system']['basepath'])) {
			$console->setOption('basepath', $data['system']['basepath']);
		}
		if (!empty($data['config']['php_path'])) {
			$console->setOption('phppath', $data['config']['php_path']);
		}
		if (!empty($data['system']['ssl_policy'])) {
			$console->setOption('sslpolicy', $data['system']['ssl_policy']);
		}
		if (!empty($data['config']['hostname'])) {
			$console->setOption('hostname', $data['config']['hostname']);
		}
		if (!empty($data['config']['admin_email'])) {
			$console->setOption('admin', $data['config']['admin_email']);
		}
		if (!empty($data['system']['default_timezone'])) {
			$console->setOption('tz', $data['system']['default_timezone']);
		}
		if (!empty($data['system']['language'])) {
			$console->setOption('lang', $data['system']['language']);
		}

		$txt = $this->dumpExecute($console);

		$dbhost = (!empty($data['database']['hostname'])) ? $data['database']['hostname'] : Installer::DEFAULT_HOST;
		$dbhost .= (!empty($data['database']['port']) ? ':' . $data['database']['port'] : '');
		$this->assertEquals($dbhost, $configCache->get('database', 'hostname'));
		$this->assertEquals($data['database']['username'], $configCache->get('database', 'username'));
		$this->assertEquals($data['database']['database'], $configCache->get('database', 'database'));
		$this->assertEquals($data['database']['password'], $configCache->get('database', 'password'));

		$this->assertEquals($data['config']['hostname'], $configCache->get('config', 'hostname'));
		$this->assertEquals($data['config']['admin_email'], $configCache->get('config', 'admin_email'));
		$this->assertEquals(trim(shell_exec('which php')), $configCache->get('config', 'php_path'));

		$this->assertEquals((!empty($data['system']['default_timezone']) ? $data['system']['default_timezone'] : Installer::DEFAULT_TZ),
			$configCache->get('system', 'default_timezone'));
		$this->assertEquals((!empty($data['system']['language']) ? $data['system']['language'] : Installer::DEFAULT_LANG),
			$configCache->get('system', 'language'));
		$this->assertEquals((!empty($data['system']['ssl_policy']) ? $data['system']['ssl_policy'] : SSL_POLICY_NONE),
			$configCache->get('system', 'ssl_policy'));
		$this->assertEquals((!empty($data['system']['urlpath']) ? $data['system']['urlpath'] : null),
			$configCache->get('system', 'urlpath'));

		$this->assertFinished($txt, true);
	}

	/**
	 * Test the automatic installation with a wrong database connection
	 */
	public function testNoDatabaseConnection()
	{
		$configCache = new ConfigCache();
		$configCache->set('system', 'basepath', $this->root->url());
		$configCache->set('config', 'php_path', trim(shell_exec('which php')));

		$this->mockApp($this->root, null, true);

		$this->configMock->shouldReceive('set')->andReturnUsing(function ($cat, $key, $value) use ($configCache) {
			if ($key !== 'basepath') {
				return $configCache->set($cat, $key, $value);
			} else {
				return true;
			}
		});;
		$this->configMock->shouldReceive('has')->andReturn(true);
		$this->configMock->shouldReceive('get')->andReturnUsing(function ($cat, $key) use ($configCache) {
			return $configCache->get($cat, $key);
		});

		$this->mockConnect(false, 1);

		$this->mockGetMarkupTemplate('local.config.tpl', 'testTemplate', 1);
		$this->mockReplaceMacros('testTemplate', \Mockery::any(), '', 1);

		$console = new AutomaticInstallation($this->consoleArgv);

		$txt = $this->dumpExecute($console);

		$this->assertStuckDB($txt);
		$this->assertTrue($this->root->hasChild('config' . DIRECTORY_SEPARATOR . 'local.config.php'));

		// Assert the default values without any config
		$this->assertEquals(Installer::DEFAULT_HOST, $configCache->get('database', 'hostname'));
		$this->assertEmpty($configCache->get('database', 'username'));
		$this->assertEmpty($configCache->get('database', 'password'));
		$this->assertEmpty($configCache->get('database', 'database'));

		$this->assertEmpty($configCache->get('config', 'hostname'), $configCache->get('config', 'hostname'));
		$this->assertEmpty($configCache->get('config', 'admin_email'), $configCache->get('config', 'admin_email'));
		$this->assertEquals(trim(shell_exec('which php')), $configCache->get('config', 'php_path'));

		$this->assertEquals(Installer::DEFAULT_TZ, $configCache->get('system', 'default_timezone'));
		$this->assertEquals(Installer::DEFAULT_LANG, $configCache->get('system', 'language'));
		$this->assertEquals(SSL_POLICY_NONE, $configCache->get('system', 'ssl_policy'));
		$this->assertEmpty($configCache->get('system', 'urlpath'), $configCache->get('system', 'urlpath'));
		$this->assertEquals($this->root->url(), $configCache->get('system', 'basepath'));
	}

	public function testGetHelp()
	{
		// Usable to purposely fail if new commands are added without taking tests into account
		$theHelp = <<<HELP
Installation - Install Friendica automatically
Synopsis
	bin/console autoinstall [-h|--help|-?] [-v] [-a] [-f]

Description
    Installs Friendica with data based on the local.config.php file or environment variables

Notes
    Not checking .htaccess/URL-Rewrite during CLI installation.

Options
    -h|--help|-?            Show help information
    -v                      Show more debug information.
    -a                      All setup checks are required (except .htaccess)
    -f|--file <config>      prepared config file (e.g. "config/local.config.php" itself) which will override every other config option - except the environment variables)
    -s|--savedb                 Save the DB credentials to the file (if environment variables is used)
    -H|--dbhost <host>          The host of the mysql/mariadb database (env MYSQL_HOST)
    -p|--dbport <port>          The port of the mysql/mariadb database (env MYSQL_PORT)
    -d|--dbdata <database>      The name of the mysql/mariadb database (env MYSQL_DATABASE)
    -U|--dbuser <username>      The username of the mysql/mariadb database login (env MYSQL_USER or MYSQL_USERNAME)
    -P|--dbpass <password>      The password of the mysql/mariadb database login (env MYSQL_PASSWORD)
    -U|--urlpath <url_path>     The URL path of Friendica - f.e. '/friendica' (env FRIENDICA_URL_PATH) 
    -B|--phppath <php_path>     The path of the PHP binary (env FRIENDICA_PHP_PATH)
    -b|--basepath <base_path>   The basepath of Friendica(env FRIENDICA_BASE_PATH)
    -S|--sslpolicy <ssl_policy> The SSL policy of Friendica (env FRIENDICA_SSL_POLICY) 
    -n|--hostname <hostname>    The hostname of Friendica (env FRIENDICA_PHP_HOSTNAME)  
    -t|--tz <timezone>          The timezone of Friendica (env FRIENDICA_TZ)
    -L|--lang <language>        The language of Friendica (env FRIENDICA_LANG)
 
Environment variables
   MYSQL_HOST                  The host of the mysql/mariadb database (mandatory if mysql and environment is used)
   MYSQL_PORT                  The port of the mysql/mariadb database
   MYSQL_USERNAME|MYSQL_USER   The username of the mysql/mariadb database login (MYSQL_USERNAME is for mysql, MYSQL_USER for mariadb)
   MYSQL_PASSWORD              The password of the mysql/mariadb database login
   MYSQL_DATABASE              The name of the mysql/mariadb database
   FRIENDICA_URL_PATH          The URL path of Friendica (f.e. '/friendica') - leave empty for auto detection
   FRIENDICA_PHP_PATH          The path of the PHP binary - leave empty for auto detection
   FRIENDICA_BASE_PATH         The basepath of Friendica - leave empty for auto detection
   FRIENDICA_ADMIN_MAIL        The admin email address of Friendica (this email will be used for admin access)
   FRIENDICA_SSL_POLICY        The SSL policy of Friendica (default is NO SSL)
   FRIENDICA_HOSTNAME          The hostname of Friendica - leave empty for auto detection
   FRIENDICA_TZ                The timezone of Friendica
   FRIENDICA_LANG              The langauge of Friendica
   
Examples
	bin/console autoinstall -f 'input.config.php
		Installs Friendica with the prepared 'input.config.php' file

	bin/console autoinstall --savedb
		Installs Friendica with environment variables and saves them to the 'config/local.config.php' file

	bin/console autoinstall -h localhost -p 3365 -U user -P passwort1234 -d friendica
		Installs Friendica with a local mysql database with credentials

HELP;

		$console = new AutomaticInstallation($this->consoleArgv);
		$console->setOption('help', true);

		$txt = $this->dumpExecute($console);

		$this->assertEquals($txt, $theHelp);
	}
}
