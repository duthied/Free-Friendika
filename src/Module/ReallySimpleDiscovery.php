<?php

namespace Friendica\Module;

use Friendica\BaseModule;
use Friendica\Core\Renderer;

/**
 * Prints the rsd.xml
 * @see http://danielberlinger.github.io/rsd/
 */
class ReallySimpleDiscovery extends BaseModule
{
	public static function rawContent()
	{
		header ("Content-Type: text/xml");
		$tpl = Renderer::getMarkupTemplate('rsd.tpl');
		echo Renderer::replaceMacros($tpl, []);
		exit();
	}
}
