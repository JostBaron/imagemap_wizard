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
 * Class/Function which renders the Witard-Form with the Data provided by the given Data-Object.
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */
require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/view/class.tx_imagemapwizard_view_abstract.php');


class tx_imagemapwizard_view_wizard extends tx_imagemapwizard_view_abstract {

	protected $doc;

	/**
	 * Just initialize the View, fill internal variables etc...
	 */
	public function init() {
		parent::init();
		$this->doc = t3lib_div::makeInstance('noDoc');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->docType = 'xhtml_trans';
		$this->doc->form = $this->getFormTag();
	}

	/**
	 * Renders Content and prints it to the screen (or any active output buffer)
	 */
	public function renderContent() {
		$this->params = t3lib_div::_GP('P');
		// Setting field-change functions:
		$fieldChangeFuncArr = $this->params['fieldChangeFunc'];
		$update = '';
		if (is_array($fieldChangeFuncArr)) {
			unset($fieldChangeFuncArr['alert']);
			foreach ($fieldChangeFuncArr as $v) {
				$update .= 'parent.opener.' . $v;
			}
		}

		$this->doc->JScode = $this->doc->wrapScriptTags('
			function checkReference()	{	//
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->params['formName'] . ' && parent.opener.document.' . $this->params['formName'] . '["' . $this->params['itemName'] . '"])	{
				  return parent.opener.document.' . $this->params['formName'] . '["' . $this->params['itemName'] . '"];
				} else {
				  close();
				}
			  }
			  function setValue(input)	{	//
				var field = checkReference();
				if (field)	{
				  field.value = input;
				  ' . $update . '
				}
			  }
			  function getValue()	{	//
				var field = checkReference();
				return field.value;
			}
		');

		$this->content .= $this->doc->startPage($GLOBALS['LANG']->getLL('imagemap_wizard.title'));

		$mainContent = $this->renderTemplate('wizard.php');
		$this->content .= $this->doc->section($GLOBALS['LANG']->getLL('imagemap_wizard.title'), $mainContent, 0, 1);
		$this->content .= $this->doc->endPage();
		$this->content = $this->insertMyStylesAndJs($this->content);

		echo $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Inserts the collected Resource-References to the Header
	 *
	 * @param String Content
	 */
	protected function insertMyStylesAndJs($content) {
		$content = str_replace('<!--###POSTJSMARKER###-->', $this->getExternalJSIncludes() . '<!--###POSTJSMARKER###-->', $content);
		$content = str_replace('<!--###POSTJSMARKER###-->', $this->getInlineJSIncludes() . '<!--###POSTJSMARKER###-->', $content);
		$content = str_replace('<!--###POSTJSMARKER###-->', $this->getExternalCSSIncludes() . '<!--###POSTJSMARKER###-->', $content);
		return $content;
	}

	/**
	 * Create a Wizard-Icon for the Link-Wizard
	 *
	 * @param String linkId ID for the id-attribute of the generated Link
	 * @param String fieldName Name of the edited field
	 * @param String fieldValue current value of the field (mostly a placeholder is used)
	 * @param String updateCallback the Javascript-Callback in case of successful change
	 * @return String Generated HTML-link to the Link-Wizard
	 */
	protected function linkWizardIcon($linkId, $fieldName, $fieldValue, $updateCallback = '') {

		$params = array(
			//'act' => 'page',
			'mode' => 'wizard',
			//'table' => 'tx_dummytable',
			'field' => $fieldName,
			'P[returnUrl]' => t3lib_div::linkThisScript(),
			'P[formName]' => $this->id,
			'P[itemName]' => $fieldName,
			'P[fieldChangeFunc][focus]' => 'focus()',
			'P[currentValue]' => $fieldValue,
			'P[pid]'=>$this->params['pid']
		);
		if ($updateCallback) {
			$params['P[fieldChangeFunc][callback]'] = $updateCallback;
		}

		$link = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('wizard_element_browser', $params);
		return "<a href=\"#\" id=\"" . $linkId . "\" onclick=\"this.blur(); vHWin=window.open('" . $link . "','','height=600,width=500,status=0,menubar=0,scrollbars=1');vHWin.focus();return false;\">" . $this->getIcon("gfx/link_popup.gif", "alt=\"" . $this->getLL('imagemap_wizard.form.area.linkwizard') . "\" title=\"" . $this->getLL('imagemap_wizard.form.area.linkwizard') . "\"") . "</a>";
	}

	/**
	 * Create a valid and unique form-tag
	 *
	 * @return String the HTML-form-tag
	 */
	protected function getFormTag() {
		return "<form id='" . $this->getId() . "' name='" . $this->getId() . "'>";
	}

	public function renderAttributesTemplate($inp) {
		$attrKeys = $this->data->getAttributeKeys();
		$ret = '';
		if (is_array($attrKeys)) {
			foreach ($attrKeys as $key) {
				$ret .= str_replace(array('ATTRLABEL', 'ATTRNAME'), array(ucfirst($key), strtolower($key)), $inp);
			}
		}
		return $ret;
	}

	public function getEmptyAttributset() {
		$attrKeys = $this->data->getAttributeKeys();
		$ret = "";
		foreach ($attrKeys as $key) {
			$ret[] = $key . ':\'\'';
		}
		return implode(',', $ret);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_wizard.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_wizard.php']);
}
?>