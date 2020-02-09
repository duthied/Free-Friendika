<?php
/**
 * @copyright Copyright (C) 2020, Friendica
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Test\src\Model\Storage;

use Friendica\Core\Config\IConfig;
use Friendica\Core\L10n;
use Friendica\Model\Storage\Filesystem;
use Friendica\Model\Storage\IStorage;
use Friendica\Test\Util\VFSTrait;
use Friendica\Util\Profiler;
use Mockery\MockInterface;
use org\bovigo\vfs\vfsStream;
use Psr\Log\NullLogger;
use function GuzzleHttp\Psr7\uri_for;

class FilesystemStorageTest extends StorageTest
{
	use VFSTrait;

	/** @var MockInterface|IConfig */
	protected $config;

	protected function setUp()
	{
		$this->setUpVfsDir();

		vfsStream::create(['storage' => []], $this->root);

		parent::setUp();
	}

	protected function getInstance()
	{
		$logger = new NullLogger();
		$profiler = \Mockery::mock(Profiler::class);
		$profiler->shouldReceive('saveTimestamp')->withAnyArgs()->andReturn(true);

		/** @var MockInterface|L10n $l10n */
		$l10n = \Mockery::mock(L10n::class)->makePartial();
		$this->config = \Mockery::mock(IConfig::class);
		$this->config->shouldReceive('get')
		             ->with('storage', 'filesystem_path', Filesystem::DEFAULT_BASE_FOLDER)
		             ->andReturn($this->root->getChild('storage')->url());

		return new Filesystem($this->config, $logger, $l10n);
	}

	protected function assertOption(IStorage $storage)
	{
		$this->assertEquals([
			'storagepath' => [
				'input', 'Storage base path',
				$this->root->getChild('storage')->url(),
				'Folder where uploaded files are saved. For maximum security, This should be a path outside web server folder tree'
			]
		], $storage->getOptions());
	}

	/**
	 * Test the exception in case of missing directorsy permissions
	 *
	 * @expectedException  \Friendica\Model\Storage\StorageException
	 * @expectedExceptionMessageRegExp /Filesystem storage failed to create \".*\". Check you write permissions./
	 */
	public function testMissingDirPermissions()
	{
		$this->root->getChild('storage')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test');
	}

	/**
	 * Test the exception in case of missing file permissions
	 *
	 * @expectedException \Friendica\Model\Storage\StorageException
	 * @expectedExceptionMessageRegExp /Filesystem storage failed to save data to \".*\". Check your write permissions/
	 */
	public function testMissingFilePermissions()
	{
		$this->markTestIncomplete("Cannot catch file_put_content() error due vfsStream failure");

		vfsStream::create(['storage' => ['f0' => ['c0' => ['k0i0' => '']]]], $this->root);

		$this->root->getChild('storage/f0/c0/k0i0')->chmod(000);

		$instance = $this->getInstance();
		$instance->put('test', 'f0c0k0i0');
	}

	/**
	 * Test the backend storage of the Filesystem Storage class
	 */
	public function testDirectoryTree()
	{
		$instance = $this->getInstance();

		$instance->put('test', 'f0c0d0i0');

		$dir = $this->root->getChild('storage/f0/c0')->url();
		$file = $this->root->getChild('storage/f0/c0/d0i0')->url();

		$this->assertDirectoryExists($dir);
		$this->assertFileExists($file);

		$this->assertDirectoryIsWritable($dir);
		$this->assertFileIsWritable($file);

		$this->assertEquals('test', file_get_contents($file));
	}
}
