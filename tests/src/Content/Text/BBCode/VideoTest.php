<?php

namespace Friendica\Test\Content\Text\BBCode;

use Friendica\Content\Text\BBCode\Video;
use Friendica\Test\MockedTest;

class VideoTest extends MockedTest
{
	public function dataVideo()
	{
		return [
			'youtube' => [
				'input' => '[video]https://youtube.link/4523[/video]',
				'assert' => '[youtube]https://youtube.link/4523[/youtube]',
			],
			'youtu.be' => [
				'input' => '[video]https://youtu.be.link/4523[/video]',
				'assert' => '[youtube]https://youtu.be.link/4523[/youtube]',
			],
			'vimeo' => [
				'input' => '[video]https://vimeo.link/2343[/video]',
				'assert' => '[vimeo]https://vimeo.link/2343[/vimeo]',
			],
			'mixed' => [
				'input' => '[video]https://vimeo.link/2343[/video] With other [b]string[/b] [video]https://youtu.be/blaa[/video]',
				'assert' => '[vimeo]https://vimeo.link/2343[/vimeo] With other [b]string[/b] [youtube]https://youtu.be/blaa[/youtube]',
			]
		];
	}

	/**
	 * Test if the BBCode is successfully transformed for video links
	 *
	 * @dataProvider dataVideo
	 */
	public function testTransform(string $input, string $assert)
	{
		$bbCodeVideo = new Video();

		$this->assertEquals($assert, $bbCodeVideo->transform($input));
	}
}
