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
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */

define('TYPO3_MOD_PATH','../typo3conf/ext/imagemap_wizard/');

$BACK_PATH = '../../../typo3/';
require($BACK_PATH.'init.php');

require_once(t3lib_extMgm::extPath('imagemap_wizard').'classes/controller/class.tx_imagemapwizard_controller_wizard.php');

$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_wizards.xml');
$GLOBALS['LANG']->includeLLFile('EXT:imagemap_wizard/locallang.xml');

$SOBE = t3lib_div::makeInstance('tx_imagemapwizard_controller_wizard');
$SOBE->triggerAction();

?>