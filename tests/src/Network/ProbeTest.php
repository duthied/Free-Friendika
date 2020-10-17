<?php

namespace Friendica\Test\src\Network;

use Friendica\Network\Probe;
use PHPUnit\Framework\TestCase;

class ProbeTest extends TestCase
{
	const TEMPLATENOBASE = '
<!DOCTYPE html>
<html lang="en-us">
<head>
    <title>Example Blog</title>
    <link href="{{$link}}" rel="alternate" type="application/rss+xml" title="Example Blog" />
	<link href="{{$link}}" rel="feed" type="application/rss+xml" title="Example Blog" />
</head>
<body>
    <p>Hello World!</p>
</body>
</html>';

	const TEMPLATEBASE = '
<!DOCTYPE html>
<html lang="en-us">
<head>
    <title>Example Blog</title>
    <link href="{{$link}}" rel="alternate" type="application/rss+xml" title="Example Blog" />
	<link href="{{$link}}" rel="feed" type="application/rss+xml" title="Example Blog" />
    <base href="{{$url}}">
</head>
<body>
    <p>Hello World!</p>
</body>
</html>';

	const EXPECTED = [
		'https://example.org/path/to/blog/index.php' => [
			'index.xml'               => 'https://example.org/path/to/blog/index.xml',
			'./index.xml'             => 'https://example.org/path/to/blog/index.xml',
			'../index.xml'            => 'https://example.org/path/to/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org/path/to/blog/' => [
			'index.xml'               => 'https://example.org/path/to/blog/index.xml',
			'./index.xml'             => 'https://example.org/path/to/blog/index.xml',
			'../index.xml'            => 'https://example.org/path/to/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org/blog/' => [
			'index.xml'               => 'https://example.org/blog/index.xml',
			'./index.xml'             => 'https://example.org/blog/index.xml',
			'../index.xml'            => 'https://example.org/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
		'https://example.org' => [
			'index.xml'               => 'https://example.org/index.xml',
			'./index.xml'             => 'https://example.org/index.xml',
			'../index.xml'            => 'https://example.org/index.xml',
			'/index.xml'              => 'https://example.org/index.xml',
			'//example.com/index.xml' => 'https://example.com/index.xml',
		],
	];

	private function replaceMacros($template, $vars)
	{
		foreach ($vars as $var => $value) {
			$template = str_replace('{{' . $var . '}}', $value, $template);
		}

		return $template;
	}

	/**
	 * @small
	 */
	public function testGetFeedLinkNoBase()
	{
		foreach (self::EXPECTED as $url => $hrefs) {
			foreach ($hrefs as $href => $expected) {
				$body = $this->replaceMacros(self::TEMPLATENOBASE, ['$link' => $href]);

				$feedLink = Probe::getFeedLink($url, $body);

				self::assertEquals($expected, $feedLink, 'base url = ' . $url . ' | href = ' . $href);
			}
		}
	}

	/**
	 * @small
	 */
	public function testGetFeedLinkBase()
	{
		foreach (self::EXPECTED as $url => $hrefs) {
			foreach ($hrefs as $href => $expected) {
				$body = $this->replaceMacros(self::TEMPLATEBASE, ['$url' => $url, '$link' => $href]);

				$feedLink = Probe::getFeedLink('http://example.com', $body);

				self::assertEquals($expected, $feedLink, 'base url = ' . $url . ' | href = ' . $href);
			}
		}
	}
}
