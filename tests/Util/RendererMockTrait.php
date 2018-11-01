<?php
/**
 * Created by PhpStorm.
 * User: philipp
 * Date: 01.11.18
 * Time: 10:08
 */

namespace Friendica\Test\Util;


use Mockery\MockInterface;

trait RendererMockTrait
{
	/**
	 * @var MockInterface The Interface for mocking a renderer
	 */
	private $rendererMock;

	public function mockGetMarkupTemplate($templateName, $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:Friendica\Core\Renderer');
		}

		$this->rendererMock
			->shouldReceive('getMarkupTemplate')
			->with($templateName)
			->times($times)
			->andReturn($return);
	}

	public function mockReplaceMacros($template, $args = [], $return = '', $times = null)
	{
		if (!isset($this->rendererMock)) {
			$this->rendererMock = \Mockery::mock('alias:Friendica\Core\Renderer');
		}

		$this->rendererMock
			->shouldReceive('replaceMacros')
			->with($template, $args)
			->times($times)
			->andReturn($return);
	}
}
