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

abstract class tx_imagemapwizard_view_abstract {

	protected $jsFiles, $cssFiles, $inlineJs, $data, $id;

	protected static $icon2Sprite = array(
		"gfx/button_up.gif" => 'actions-move-up',
		"gfx/button_down.gif" => 'actions-move-down',
		"gfx/undo.gif" => 'actions-edit-undo',
		"gfx/redo.gif" => 'extensions-imagemap_wizard-redo',
		"gfx/garbage.gif" => 'actions-edit-delete',
		"gfx/add.gif" => 'actions-edit-add',
		"gfx/refresh_n.gif" => 'actions-system-refresh',
		"gfx/pil2down.gif" => 'actions-view-table-expand',
		"gfx/pil2up.gif" => 'actions-view-table-collapse',
		"gfx/link_popup.gif" => 'extensions-imagemap_wizard-link',
		"gfx/zoom_in.gif" => 'extensions-imagemap_wizard-zoomin',
		"gfx/zoom_out.gif" => 'extensions-imagemap_wizard-zoomout',
		"gfx/arrowup.png" => 'actions-view-go-up',
		"gfx/arrowdown.png" => 'actions-view-go-down',
		"gfx/close_gray.gif" => 'actions-document-close',
	);

	/**
	 * Just initialize the View, fill internal variables etc...
	 */
	public function init() {
		$this->id = "imagemap" . t3lib_div::shortMD5(rand(1, 100000));
		$this->jsFiles = array();
		$this->cssFiles = array();
	}

	public function getId() {
		return $this->id;
	}

	/**
	 * Sets the relates Data-Model-Object
	 *
	 * @param tx_imagemapwizard_dataObject Data-Object
	 */
	public function setData(tx_imagemapwizard_model_dataObject $data) {
		$this->data = $data;
	}

	/**
	 * Collect required Javascript-Resoucres
	 *
	 * @param String Filename
	 */
	protected function addExternalJS($file) {
		if (!in_array($file, $this->jsFiles)) {
			$this->jsFiles[] = $file;
		}
	}

	/**
	 * Collect required Inline-Javascript.
	 *
	 * @param String Javascript-Block
	 */
	protected function addInlineJS($js) {
		$this->inlineJs .= "\n\n" . $js;
	}


	/**
	 * Collect required CSS-Resoucres
	 *
	 * @param String Filename
	 */
	protected function addExternalCSS($file) {
		if (!in_array($file, $this->cssFiles)) {
			$this->cssFiles[] = $file;
		}
	}

	protected function getExternalJSIncludes() {
		$extPath = tx_imagemapwizard_model_typo3env::getExtBackPath('imagemap_wizard');

		if (is_array($this->jsFiles)) {
			foreach ($this->jsFiles as $file) {
				$ret .= "\n<script type=\"text/javascript\" src=\"" . $backPath . $extPath . $file . "\"></script>";
			}
		}
		return $ret;
	}

	protected function getInlineJSIncludes() {
		return trim($this->inlineJs) ? ('<script type="text/javascript">' . trim($this->inlineJs) . '</script>') : '';
	}

	protected function getExternalCSSIncludes() {
		$backPath = tx_imagemapwizard_model_typo3env::getBackPath();
		$extPath = str_replace(PATH_site, '', t3lib_extMgm::extPath('imagemap_wizard'));
		if (is_array($this->cssFiles)) {
			foreach ($this->cssFiles as $file) {
				$ret .= "\n<link rel=\"stylesheet\" type=\"text/css\" href=\"" . $backPath . $extPath . $file . "\" />";
			}
		}
		return $ret;
	}

	protected function renderTemplate($file) {
		ob_start();
		include(t3lib_extMgm::extPath('imagemap_wizard') . 'templates/' . $file);
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}

	protected function getAjaxURL($script) {
		return tx_imagemapwizard_model_typo3env::getExtBackPath('imagemap_wizard') . $script;
	}

	protected function getLL($label, $printIt = false) {
		$value = $GLOBALS['LANG']->getLL($label);
		if ($printIt) {
			echo $value;
		}
		return $value;
	}

	/**
	 * Create a img-tag with a TYPO3-Skinicon
	 *
	 * @param String skinPath the Path to the TYPO3-icon
	 * @param String attr additional required attributes
	 * @return String HTML-img-tag
	 */
	protected function getIcon($skinPath, $attr = '') {

		if (version_compare(TYPO3_version, '4.4', '<')) {
			$source = t3lib_iconWorks::skinImg($this->doc->backPath, $skinPath);
			$match = array();
			if (preg_match('/src="(\S+)"/', $source, $match) && !is_file($match[1])) {
				$source = 'src ="' . $this->getTplSubpath() . $skinPath . '"';
			}
			return "<img " . $source . ($attr ? ' ' . $attr : '') . " />";
		} else {
			return '<span ' . $attr . '>' . t3lib_iconWorks::getSpriteIcon(self::$icon2Sprite[$skinPath]) . '</span>';
		}
	}

	/**
	 *   Determine path to the view-templates
	 *   Just a shortcut to reduce the code within the view's
	 *
	 *   @return string	  relative path to the template folder
	 */
	protected function getTplSubpath() {
		return tx_imagemapwizard_model_typo3env::getExtBackPath('imagemap_wizard') . 'templates/';
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_abstract.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/view/class.tx_imagemapwizard_view_abstract.php']);
}
?>