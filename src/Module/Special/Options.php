<?php

namespace Friendica\Module\Special;

use Friendica\App\Router;
use Friendica\BaseModule;

class Options extends BaseModule
{
	protected function options(array $request = [])
	{
		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		$this->response->setHeader(implode(',', Router::ALLOWED_METHODS), 'Allow');
		$this->response->setStatus(204);
	}
}
