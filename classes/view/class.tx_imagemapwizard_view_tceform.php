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
 * Class/Function which renders the TCE-Form with the Data provided by the given Data-Object.
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */

require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/model/class.tx_imagemapwizard_model_typo3env.php');
require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/view/class.tx_imagemapwizard_view_abstract.php');


class tx_imagemapwizard_view_tceform extends tx_imagemapwizard_view_abstract {

	protected $form, $formName, $wizardConf;

	public function setTCEForm($form) {
		$this->form = $form;
	}

	/**
	 * Renders Content and prints it to the screen (or any active output buffer)
	 *
	 * @return string	 the rendered form content
	 */
	public function renderContent() {
		if (!$this->data->hasValidImageFile()) {
			$content = $this->form->sL('LLL:EXT:imagemap_wizard/locallang.xml:form.no_image');
		} else {
			$content = $this->renderTemplate('tceform.php');
			$this->form->additionalCode_pre[] = $this->getExternalJSIncludes();
			$this->form->additionalCode_pre[] = $this->getInlineJSIncludes();
		}
		return $content;
	}

	public function setWizardConf($wConf) {
		$this->wizardConf = $wConf;
	}

	public function setFormName($name) {
		$this->formName = $name;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_tceform.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_tceform.php']);
}
?>