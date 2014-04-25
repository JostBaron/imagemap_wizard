<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008 Tolleiv Nietsch (info@tolleiv.de)
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class/Function provides various convertion-methods for Map-Data and generates a HTML-Imagemap out of a given pseudo-map
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */
class tx_imagemapwizard_model_mapper {

	/**
	 * Generate a HTML-Imagemap using Typolink etc..
	 *
	 * @param tslib_cObj $cObj	 cObj cObject we used for genenerating the Links
	 * @param string $name	Name of the generated map
	 * @param string $mapping	mapping the XML_pseudo-imagemap
	 * @param string $whitelist
	 * @param boolean $xhtml
	 * @param array $conf
	 * @param int $mapNo
	 * @return string the valid HTML-imagemap (hopefully valid)
	 */
	public function generateMap(tslib_cObj &$cObj, $name, $mapping = NULL, $whitelist = NULL, $xhtml = NULL, $conf = NULL, $mapNo = 0) {
		$useWhitelist = is_array($whitelist);
		if ($useWhitelist) {
			$whitelist = array_flip($whitelist);
		}
		//$helper = t3lib_div::makeInstance('tx_imagemapwizard_mapconverter');
		$mapArray = self::map2array($mapping);

		$mapArray['@']['name'] = $this->createValidNameAttribute($name);
		// use id-attribute if XHTML is required see issue #2525
		// name-attribute is still required due to browser compatibility ;(
		if ($xhtml) {
			$mapArray['@']['id'] = $mapArray['@']['name'];
		}

		if (!is_array($conf) || !array_key_exists('area.', $conf)) {
			$conf = array('area.' => array());
		}

		while (is_array($mapArray['#']) && (list($key, $node) = each($mapArray['#']))) {
			if (!$node['value'] && !$node['@']['href']) continue;

			$reg = array('area-href' => $node["value"]);
			foreach ($node['@'] as $ak => $av) {
				$reg['area-' . $ak] = htmlspecialchars($av);
			}
			$cObj->LOAD_REGISTER($reg, 'LOAD_REGISTER');
			$tmp = self::map2array($cObj->typolink('-', $this->getTypolinkSetup(($node['value'] ? $node['value'] : $node['@']['href']), $conf['area.'])), 'a');
			$cObj->LOAD_REGISTER($reg, 'RESTORE_REGISTER');
			if (is_array($tmp['@'])) {
				unset($mapArray['#'][$key]['@']['href']);
				$mapArray['#'][$key]['@'] = array_merge(array_filter($tmp['@']), array_filter($mapArray['#'][$key]['@']));

				if ($useWhitelist) {
					$mapArray['#'][$key]['@'] = array_intersect_key($mapArray['#'][$key]['@'], $whitelist);
				}
				//Remove emoty attributes
				$mapArray['#'][$key]['@'] = array_filter($mapArray['#'][$key]['@']);

				// TODO: if(!isset($mapArray['#'][$key]['@']['href']))... what to do here???
			}
			unset($mapArray['#'][$key]['value']);
		}
		return (self::isEmptyMap($mapArray) ? '' : self::array2map($mapArray));
	}

	/**
	 * Encapsulates the creation of valid HTML-imagemap-names
	 *
	 * @param String value
	 * @return String transformed value
	 */
	public function createValidNameAttribute($value) {

		if (!preg_match('/\S+/', $value)) {
			$value = t3lib_div::shortMD5(rand(0, 100));
		}
		$name = preg_replace('/[^a-zA-Z0-9\-_]/i', '-', $value); // replace any special character with an dash
		$name = preg_replace('/\-+$/', '', $name); // remove trailing dashes

		while (!preg_match('/^[a-zA-Z]{3}/', $name)) {
			$name = chr(rand(97, 122)) . $name;
		}
		return $name;
	}

	/**
	 * Encapsulates the creation of a valid typolink-conf array
	 *
	 * @param String param the paramater which is used for the link-generation
	 * @return Array typolink-conf array
	 */
	protected function getTypolinkSetup($param, $conf = NULL) {
		$ret = array('parameter.' => array('wrap'=>$param));
		if (is_array($conf) && array_key_exists('typolink.', $conf) && is_array($conf['typolink.'])) {
			$ret = array_merge($ret, $conf['typolink.']);
		}
		return $ret;
	}


	/**
	 * Convert XML into a lightweight Array, keep Attributes, Values etc,
	 * is limited to one level (no recursion) since this is enough for the imagemap
	 *
	 * @see	 array2map
	 * @param String value the XML-map
	 * @param String basetag the Root-Tag of the resulting Array
	 * @return Array transformed Array keys: 'name'~Tagname, 'value'~Tagvalue, '@'~Sub-Array with Attributes, '#'~Sub-Array with Childnodes
	 */
	public static function map2array($value, $basetag = 'map') {
		if (!strlen($value) || !is_string($value)) {
			$value = '<map></map>';
		}
		$ret = array('name' => $basetag);
		if (!($xml = @simplexml_load_string($value))) {
			return $ret;
		}

		if (!($xml->getName() == $basetag)) {
			return $ret;
		}

		if (self::nodeHasAttributes($xml)) {
			$ret['@'] = self::getAttributesFromXMLNode($xml);
		}
		$ret['#'] = array();
		foreach ($xml->children() as $subNode) {
			$newChild = array();
			$newChild['name'] = $subNode->getName();
			if ((string) $subNode) {
				$newChild['value'] = (string) $subNode;
			}
			if (self::nodeHasAttributes($subNode)) {
				$newChild['@'] = self::getAttributesFromXMLNode($subNode);
			}
			$ret['#'][] = $newChild;
		}
		if (!count($ret['#'])) unset($ret['#']);
		return $ret;
	}

	/**
	 * Convert a PHP-Array into a XML-Structure
	 *
	 * @see map2array
	 * @param Array value a Array which uses the same notation as described above
	 * @param Integer level counting the current level for recursion...
	 * @return String XML-String
	 */
	public static function array2map($value, $level = 0) {
		if ((!$value['name']) && ($level == 0)) {
			$value['name'] = 'map';
		}
		$ret = NULL;
		if (!$value['#'] && !$value['value']) {
			$ret = '<' . $value['name'] . self::implodeXMLAttributes($value['@']) . ' />';
		} else {
			$ret = '<' . $value['name'] . self::implodeXMLAttributes($value['@']) . '>';
			while (is_array($value['#']) && (list(, $subNode) = each($value['#']))) {
				$ret .= self::array2map($subNode, $level + 1);
			}
			$ret .= $value['value'];
			$ret .= '</' . $value['name'] . '>';
		}
		return $ret;
	}

	/**
	 * compare two maps
	 *
	 * @param	string  $map1   first imagemap
	 * @param	string  $map2   second imagemap
	 * @return   boolean		 determines whether the maps match or not
	 * @see arrays_match
	 */
	public function compareMaps($map1, $map2) {
		$arrayMap1 = self::map2array($map1);
		$arrayMap2 = self::map2array($map2);
		return self::arrays_match($arrayMap1, $arrayMap2);
	}


	/**
	 * Encapsulate the extraction of Attributes out of the SimpleXML-Structure
	 *
	 * @param SimpleXMLNode node
	 * @param String attr determindes if a single of (if empty) all attributes should be extracted
	 * @return Mixed Extracted attribute(s)
	 *
	 */
	protected static function getAttributesFromXMLNode($node, $attr = NULL) {
		$tmp = (array) $node->attributes();
		return ($attr == NULL) ? $tmp['@attributes'] : (string) $tmp['@attributes'][$attr];
	}

	/**
	 * Check whether a node has any attributes or not
	 *
	 * @param SimpleXMLNode node
	 * @return Boolean
	 */
	protected static function nodeHasAttributes($node) {
		return is_array(self::getAttributesFromXMLNode($node));
	}

	/**
	 * Combines a array of attributes into a HTML-conform list
	 *
	 * @param Array attributes
	 * @return String
	 */
	protected static function implodeXMLAttributes($attributes) {
		if (!is_array($attributes)) {
			return '';
		}
		$ret = '';
		foreach ($attributes as $key => $value) {
			$ret .= sprintf(' %s="%s"', $key, htmlspecialchars($value));
		}
		return $ret;
	}

	/**
	 * compare two element recursivly and check whether the content of both match
	 * order of elements is not important, just that every content related to a key is matching within these arrays
	 *
	 * @param	mixed  $a   first element
	 * @param	mixed  $b   second element
	 * @return   boolean	 determine whether elements match of not
	 */
	protected static function arrays_match($a, $b) {
		if (!is_array($a) || !is_array($b)) {
			return $a == $b;
		}
		$match = true;
		foreach ($a as $key => $value) {
			$match = $match && self::arrays_match($a[$key], $b[$key]);
		}
		foreach ($b as $key => $value) {
			$match = $match && self::arrays_match($b[$key], $a[$key]);
		}
		return $match;
	}

	/**
	 * check whether a given string is a valid imagemap
	 * the check is not very robust so far but it resolves all required situations (see unit-tests)
	 *
	 * @param	mixed   $map	the value which is supposed to be a imagemap
	 * @return   boolean	 determine whether the valued passed the test or not
	 */
	public static function isEmptyMap($map) {
		$arr = is_array($map) ? $map : self::map2array($map);
		return !(count($arr['#']) > 0);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_mapper.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_mapper.php']);
}


?>