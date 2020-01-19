<?php
/**
 * @file tests/src/Util/XmlTest.php
 */
namespace Friendica\Test\src\Util;

use Friendica\Util\XML;
use PHPUnit\Framework\TestCase;

/**
 * XML utility test class
 */
class XmlTest extends TestCase
{
    /**
	* escape and unescape
	*/
	public function testEscapeUnescape()
	{
		$text="<tag>I want to break\n this!11!<?hard?></tag>";
		$xml=XML::escape($text);
		$retext=XML::unescape($text);
		$this->assertEquals($text, $retext);
    }
    
	/**
	 * escape and put in a document
	 */
	public function testEscapeDocument()
	{
		$tag="<tag>I want to break</tag>";
		$xml=XML::escape($tag);
		$text='<text>'.$xml.'</text>';
		$xml_parser=xml_parser_create();
		//should be possible to parse it
		$values=array();
		$index=array();
		$this->assertEquals(1, xml_parse_into_struct($xml_parser, $text, $values, $index));
		$this->assertEquals(
			array('TEXT'=>array(0)),
			$index
		);
		$this->assertEquals(
			array(array('tag'=>'TEXT', 'type'=>'complete', 'level'=>1, 'value'=>$tag)),
			$values
		);
		xml_parser_free($xml_parser);
	}
}
