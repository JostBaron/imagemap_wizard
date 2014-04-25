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

class tx_imagemapwizard_wizicon {

	/**
	 * Adds wizard icon
	 *
	 * @param   array	  Input array with wizard items for plugins
	 * @return   array	  Modified input array, having the item added.
	 */
	function proc($wizardItems) {

		$LL = $this->includeLocalLang();

		$newWizardItem['common_imagemap'] = array(
			'icon' => t3lib_extMgm::extRelPath('imagemap_wizard') . 'tt_content_imagemap.gif',
			'title' => $GLOBALS['LANG']->getLLL('imagemap.title', $LL),
			'description' => $GLOBALS['LANG']->getLLL('imagemap.description', $LL),
			'tt_content_defValues.' => array(
				'CType' => 'imagemap_wizard'
			)
		);

		$specialPart = is_array($wizardItems) ? $wizardItems : array();
		$commonPart = array_splice($specialPart, 0, $this->getCommonItemCount($wizardItems));

		return array_merge($commonPart, $newWizardItem, $specialPart);
	}

	/**
	 * [Describe function...]
	 *
	 * @param   [type]	  $list: ...
	 * @return   [type]	  ...
	 */
	function getCommonItemCount($list) {
		if (!is_array($list)) {
			return;
		}
		reset($list);
		$num = 0;
		while (preg_match('/^common/', key($list)) && next($list)) {
			$num++;
		}
		return $num;
	}


	/**
	 * Includes the locallang file
	 *
	 * @return array	The LOCAL_LANG array
	 */
	function includeLocalLang() {
		$llFile = t3lib_extMgm::extPath('imagemap_wizard') . 'locallang.xml';
		if (version_compare(TYPO3_version, '4.6.0', '<')) {
			$LOCAL_LANG = t3lib_div::readLLXMLfile($llFile, $GLOBALS['LANG']->lang);
		} else {
			/** @var $localizationParser t3lib_l10n_parser_Llxml */
			$localizationParser = t3lib_div::makeInstance('t3lib_l10n_parser_Llxml');
			$LOCAL_LANG = $localizationParser->getParsedData($llFile, $GLOBALS['LANG']->lang);
		}
		return $LOCAL_LANG;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/class.tx_imagemapwizard_wizicon.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/class.tx_imagemapwizard_wizicon.php']);
}

?>
