<?php
/**
 * @brief This class contain functions to work with XML data
 *
 */
class xml {
	function from_array($array, &$xml) {

		if (!is_object($xml)) {
			foreach($array as $key => $value) {
				$root = new SimpleXMLElement("<".$key."/>");
				self::from_array($value, $root);

				$dom = dom_import_simplexml($root)->ownerDocument;
				$dom->formatOutput = true;
				$xml = $dom;
				return $dom->saveXML();
			}
		}

		foreach($array as $key => $value) {
			if (!is_array($value) AND !is_numeric($key))
				$xml->addChild($key, xmlify($value));
			elseif (is_array($value))
				self::from_array($value, $xml->addChild($key));
		}
	}

	function copy(&$source, &$target, $elementname) {
		if (count($source->children()) == 0)
			$target->addChild($elementname, $source);
		else {
			$child = $target->addChild($elementname);
			foreach ($source->children() AS $childfield => $childentry)
				self::copy($childentry, $child, $childfield);
		}
	}
}
?>
