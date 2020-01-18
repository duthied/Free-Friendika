<?php

// contains a test-hook call for creating a storage instance

use Friendica\App;
use Friendica\Core\L10n;
use Friendica\Test\Util\SampleStorageBackend;
use Mockery\MockInterface;

function create_instance(App $a, &$data)
{
	/** @var L10n|MockInterface $l10n */
	$l10n = \Mockery::mock(L10n::class);

	if ($data['name'] == SampleStorageBackend::getName()) {
		$data['storage'] = new SampleStorageBackend($l10n);
	}
}
