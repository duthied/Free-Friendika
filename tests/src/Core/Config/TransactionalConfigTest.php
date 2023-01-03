<?php

namespace Friendica\Test\src\Core\Config;
use Friendica\Core\Config\Capability\ISetConfigValuesTransactional;
use Friendica\Core\Config\Model\Config;
use Friendica\Core\Config\Model\TransactionalConfig;
use Friendica\Core\Config\Util\ConfigFileManager;
use Friendica\Core\Config\ValueObject\Cache;
use Friendica\Test\MockedTest;
use Friendica\Test\Util\VFSTrait;

class TransactionalConfigTest extends MockedTest
{
	use VFSTrait;

	/** @var ConfigFileManager */
	protected $configFileManager;

	protected function setUp(): void
	{
		parent::setUp();

		$this->setUpVfsDir();

		$this->configFileManager = new ConfigFileManager($this->root->url(), $this->root->url() . '/config/', $this->root->url() . '/static/');
	}

	public function dataTests(): array
	{
		return [
			'default' => [
				'data' => include dirname(__FILE__, 4) . '/datasets/B.node.config.php',
			]
		];
	}

	public function testInstance()
	{
		$config = new Config($this->configFileManager, new Cache());
		$transactionalConfig = new TransactionalConfig($config);

		self::assertInstanceOf(ISetConfigValuesTransactional::class, $transactionalConfig);
		self::assertInstanceOf(TransactionalConfig::class, $transactionalConfig);
	}

	public function testTransactionalConfig()
	{
		$config = new Config($this->configFileManager, new Cache());
		$config->set('config', 'key1', 'value1');
		$config->set('system', 'key2', 'value2');
		$config->set('system', 'keyDel', 'valueDel');
		$config->set('delete', 'keyDel', 'catDel');

		$transactionalConfig = new TransactionalConfig($config);
		self::assertEquals('value1', $transactionalConfig->get('config', 'key1'));
		self::assertEquals('value2', $transactionalConfig->get('system', 'key2'));
		self::assertEquals('valueDel', $transactionalConfig->get('system', 'keyDel'));
		self::assertEquals('catDel', $transactionalConfig->get('delete', 'keyDel'));
		// the config file knows it as well immediately
		$tempData = include $this->root->url() . '/config/' . ConfigFileManager::CONFIG_DATA_FILE;
		self::assertEquals('value1', $tempData['config']['key1'] ?? null);
		self::assertEquals('value2', $tempData['system']['key2'] ?? null);

		// new key-value
		$transactionalConfig->set('transaction', 'key3', 'value3');
		// overwrite key-value
		$transactionalConfig->set('config', 'key1', 'changedValue1');
		// delete key-value
		$transactionalConfig->delete('system', 'keyDel');
		// delete last key of category - so the category is gone
		$transactionalConfig->delete('delete', 'keyDel');

		// The main config still doesn't know about the change
		self::assertNull($config->get('transaction', 'key3'));
		self::assertEquals('value1', $config->get('config', 'key1'));
		self::assertEquals('valueDel', $config->get('system', 'keyDel'));
		self::assertEquals('catDel', $config->get('delete', 'keyDel'));
		// but the transaction config of course knows it
		self::assertEquals('value3', $transactionalConfig->get('transaction', 'key3'));
		self::assertEquals('changedValue1', $transactionalConfig->get('config', 'key1'));
		self::assertNull($transactionalConfig->get('system', 'keyDel'));
		self::assertNull($transactionalConfig->get('delete', 'keyDel'));
		// The config file still doesn't know it either
		$tempData = include $this->root->url() . '/config/' . ConfigFileManager::CONFIG_DATA_FILE;
		self::assertEquals('value1', $tempData['config']['key1'] ?? null);
		self::assertEquals('value2', $tempData['system']['key2'] ?? null);
		self::assertEquals('catDel', $tempData['delete']['keyDel'] ?? null);
		self::assertNull($tempData['transaction']['key3'] ?? null);

		// save it back!
		$transactionalConfig->save();

		// Now every config and file knows the change
		self::assertEquals('changedValue1', $config->get('config', 'key1'));
		self::assertEquals('value3', $config->get('transaction', 'key3'));
		self::assertNull($config->get('system', 'keyDel'));
		self::assertNull($config->get('delete', 'keyDel'));
		self::assertEquals('value3', $transactionalConfig->get('transaction', 'key3'));
		self::assertEquals('changedValue1', $transactionalConfig->get('config', 'key1'));
		self::assertNull($transactionalConfig->get('system', 'keyDel'));
		$tempData = include $this->root->url() . '/config/' . ConfigFileManager::CONFIG_DATA_FILE;
		self::assertEquals('changedValue1', $tempData['config']['key1'] ?? null);
		self::assertEquals('value2', $tempData['system']['key2'] ?? null);
		self::assertEquals('value3', $tempData['transaction']['key3'] ?? null);
		self::assertNull($tempData['system']['keyDel'] ?? null);
		self::assertNull($tempData['delete']['keyDel'] ?? null);
		// the whole category should be gone
		self::assertNull($tempData['delete'] ?? null);
	}
}
