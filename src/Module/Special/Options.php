<?php

namespace Friendica\Module\Special;

use Friendica\App\Router;
use Friendica\BaseModule;

class Options extends BaseModule
{
	protected function options(array $request = [])
	{
		// @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Methods/OPTIONS
		$this->response->setHeader('Allow', implode(',', Router::ALLOWED_METHODS));
		$this->response->setHeader(($this->server['SERVER_PROTOCOL'] ?? 'HTTP/1.1') . ' 204 No Content');
	}
}
