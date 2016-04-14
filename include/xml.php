<?php
/**
 * @file include/xml.php
 */


/**
 * @brief This class contain functions to work with XML data
 *
 */
class xml {
	/**
	 * @brief Creates an XML structure out of a given array
	 *
	 * @param array $array The array of the XML structure that will be generated
	 * @param object $xml The createdXML will be returned by reference
	 * @param bool $remove_header Should the XML header be removed or not?
	 * @param array $namespaces List of namespaces
	 * @param bool $root - interally used parameter. Mustn't be used from outside.
	 *
	 * @return string The created XML
	 */
	public static function from_array($array, &$xml, $remove_header = false, $namespaces = array(), $root = true) {

		if ($root) {
			foreach($array as $key => $value) {
				foreach ($namespaces AS $nskey => $nsvalue)
					$key .= " xmlns".($nskey == "" ? "":":").$nskey.'="'.$nsvalue.'"';

				$root = new SimpleXMLElement("<".$key."/>");
				self::from_array($value, $root, $remove_header, $namespaces, false);

				$dom = dom_import_simplexml($root)->ownerDocument;
				$dom->formatOutput = true;
				$xml = $dom;

				$xml_text = $dom->saveXML();

				if ($remove_header)
					$xml_text = trim(substr($xml_text, 21));

				return $xml_text;
			}
		}

		foreach($array as $key => $value) {
			if ($key == "@attributes") {
				if (!isset($element) OR !is_array($value))
					continue;

				foreach ($value as $attr_key => $attr_value) {
					$element_parts = explode(":", $attr_key);
					if ((count($element_parts) > 1) AND isset($namespaces[$element_parts[0]]))
						$namespace = $namespaces[$element_parts[0]];
					else
						$namespace = NULL;

					$element->addAttribute ($attr_key, $attr_value, $namespace);
				}

				continue;
			}

			$element_parts = explode(":", $key);
			if ((count($element_parts) > 1) AND isset($namespaces[$element_parts[0]]))
				$namespace = $namespaces[$element_parts[0]];
			else
				$namespace = NULL;

			if (!is_array($value))
				$element = $xml->addChild($key, xmlify($value), $namespace);
			elseif (is_array($value)) {
				$element = $xml->addChild($key, NULL, $namespace);
				self::from_array($value, $element, $remove_header, $namespaces, false);
			}
		}
	}

	/**
	 * @brief Copies an XML object
	 *
	 * @param object $source The XML source
	 * @param object $target The XML target
	 * @param string $elementname Name of the XML element of the target
	 */
	public static function copy(&$source, &$target, $elementname) {
		if (count($source->children()) == 0)
			$target->addChild($elementname, xmlify($source));
		else {
			$child = $target->addChild($elementname);
			foreach ($source->children() AS $childfield => $childentry)
				self::copy($childentry, $child, $childfield);
		}
	}

	/**
	 * @brief Create an XML element
	 *
	 * @param object $doc XML root
	 * @param string $element XML element name
	 * @param string $value XML value
	 * @param array $attributes array containing the attributes
	 *
	 * @return object XML element object
	 */
	public static function create_element($doc, $element, $value = "", $attributes = array()) {
		$element = $doc->createElement($element, xmlify($value));

		foreach ($attributes AS $key => $value) {
			$attribute = $doc->createAttribute($key);
			$attribute->value = xmlify($value);
			$element->appendChild($attribute);
		}
		return $element;
	}

	/**
	 * @brief Create an XML and append it to the parent object
	 *
	 * @param object $doc XML root
	 * @param object $parent parent object
	 * @param string $element XML element name
	 * @param string $value XML value
	 * @param array $attributes array containing the attributes
	 */
	public static function add_element($doc, $parent, $element, $value = "", $attributes = array()) {
		$element = self::create_element($doc, $element, $value, $attributes);
		$parent->appendChild($element);
	}
}
?>
