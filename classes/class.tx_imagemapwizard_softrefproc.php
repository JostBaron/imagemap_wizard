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
 * Class holds required functionality to extect the references out of the pseudo-map format.
 * You can use it with the "tx_imagemapwizard" key as softref-type.
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */

require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/model/class.tx_imagemapwizard_model_mapper.php');


class tx_imagemapwizard_softrefproc extends t3lib_softrefproc {

	/**
	 * Just overrides the method which normally catches all softref-types
	 * In this case we already know what type it is ;)
	 *
	 * @param String table
	 * @param String field
	 * @param String uid
	 * @param String content
	 * @param String spKey
	 * @param Array spParams
	 * @param String structurePath
	 * @return Array the Array which describes what references we found and where ...
	 */

	function findRef($table, $field, $uid, $content, $spKey, $spParams, $structurePath = '') {
		$conv = t3lib_div::makeInstance("tx_imagemapwizard_model_mapper");
		$data = $conv->map2array($content);
		$idx = 0;

		$zeroToken = $this->makeTokenID('setTypoLinkPartsElement:' . $idx) . ':0';
		$elements = array();
		$links = array();
		if (is_array($data['#'])) {
			foreach ($data['#'] as $key => $value) {
				$tmp = $this->findRef_typolink($value['value'], $spParams);
				$linkData = $tmp['elements'][$zeroToken];

				$newToken = $this->makeTokenID('setTypoLinkPartsElement:' . $idx);
				$data['#'][$key]['value'] = str_replace($linkData['subst']['tokenID'], $newToken, $tmp['content']);
				$linkData['subst']['tokenID'] = $newToken;
				$elements[$newToken . ':' . $idx] = $linkData;
				$idx++;
			}
			reset($elements);
			reset($data['#']);
		}
		return array("content" => $conv->array2map($data), "elements" => $elements);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/class.tx_imagemapwizard_softrefproc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/class.tx_imagemapwizard_softrefproc.php']);
}

?>