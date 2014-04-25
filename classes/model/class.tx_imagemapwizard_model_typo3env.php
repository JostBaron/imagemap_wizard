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
 * Class/Function encapsulates TYPO3 enviromental operations
 *
 * @author	Tolleiv Nietsch <info@tolleiv.de>
 */

class tx_imagemapwizard_model_typo3env {
	protected $lastError;
	protected $BE_USER = NULL;
	protected $BE_USER_GLOBAL = NULL;

	/**
	 * Initialize TSFE so that the Frontend-Stuff can also be used in the Backend
	 *
	 * @param	Integer		pid The pid if the page which is simulated
	 * @return	Boolean		returns success of the operation
	 */
	public function initTSFE($pid = 1, $ws = 0) {
		/* local includes otherwise XCLASSES might be lost due to extension load order */

		$tca = $GLOBALS['TCA'];
		$GLOBALS['TT'] = t3lib_div::makeInstance('t3lib_timeTrack');
		$GLOBALS['TT']->start();

		$GLOBALS['TSFE'] = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pid, '0', 0, '', '', '', '');

		$GLOBALS['TSFE']->config['config']['language'] = $_GET['L'];
		$GLOBALS['TSFE']->id = $pid;
		$GLOBALS['TSFE']->workspacePreview = $GLOBALS['BE_USER']->workspace;
		$GLOBALS['TSFE']->connectToDB();
		$sqlDebug = $GLOBALS['TYPO3_DB']->debugOutput;
		$GLOBALS['TYPO3_DB']->debugOutput = false;
		$GLOBALS['TSFE']->initLLVars();
		$GLOBALS['TSFE']->initFEuser();
		$GLOBALS['TSFE']->sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		//$GLOBALS['TSFE']->sys_page->init($GLOBALS['TSFE']->showHiddenPage);
		$GLOBALS['TSFE']->sys_page->init(true);
		$page = $GLOBALS['TSFE']->sys_page->getPage($pid);
		if (count($page) == 0 && $GLOBALS['BE_USER']->workspace != 0) {
			$GLOBALS['TSFE']->sys_page->versioningPreview = TRUE;
			$wsRec = t3lib_beFunc::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, 'pages', $pid);
			$page = $GLOBALS['TSFE']->sys_page->getPage($wsRec['uid']);
			if (count($page) == 0) {
				$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
				$this->lastError = "Error(" . __LINE__ . ") [ Unable to find the requested host-page ]:" . $sqlDebug;
				return false;
			}
		}
		if ($page['doktype'] == 4 && count($GLOBALS['TSFE']->getPageShortcut($page['shortcut'], $page['shortcut_mode'], $page['uid'])) == 0) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			$this->lastError = "Error(" . __LINE__ . ") [ The parent-page is a shortcut therefor preview won't render properly ] :" . $sqlDebug;
			//return false; we continue using the TSFE but write down that there's something which was wrong
		}
		if ($page['doktype'] == 199 || $page['doktype'] == 254) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			$this->lastError = "Error(" . __LINE__ . ") [ The parent-page is a recycle or sysfolder therefor the preview won't render properly ]:" . $sqlDebug;
			//return false; we continue using the TSFE but write down that there's something which was wrong
		}
		$GLOBALS['TSFE']->showHiddenRecords = true;
		$GLOBALS['TSFE']->getPageAndRootline();
		$GLOBALS['TSFE']->initTemplate();
		//$GLOBALS['TSFE']->forceTemplateParsing = 1;
		$GLOBALS['TSFE']->tmpl->start($GLOBALS['TSFE']->rootLine);
		$GLOBALS['TSFE']->sPre = $GLOBALS['TSFE']->tmpl->setup['types.'][$GLOBALS['TSFE']->type]; // toplevel - objArrayName
		$GLOBALS['TSFE']->pSetup = $GLOBALS['TSFE']->tmpl->setup[$GLOBALS['TSFE']->sPre . '.'];
		if (!$GLOBALS['TSFE']->tmpl->loaded || ($GLOBALS['TSFE']->tmpl->loaded && !$GLOBALS['TSFE']->pSetup)) {
			$GLOBALS['TYPO3_DB']->debugOutput = $sqlDebug;
			$this->lastError = "Error(" . __LINE__ . ") [ template not loaded as supposed ] :" . $sqlDebug;
			//return false;   we continue using the TSFE but write down that there's something which was wrong
		}
		$GLOBALS['TSFE']->getConfigArray();
		$GLOBALS['TSFE']->TYPO3_CONF_VARS['EXT']['extCache'] = 0;
		$GLOBALS['TSFE']->getCompressedTCarray();
		$GLOBALS['TSFE']->inituserGroups();
		unset($GLOBALS['TSFE']->TYPO3_CONF_VARS['FE']['pageNotFound_handling']);
		$GLOBALS['TSFE']->determineId();
		$GLOBALS['TSFE']->newCObj();
		$GLOBALS['TCA'] = $tca; //todo: check why TCA is lost sometimes...

		return true;
	}

	/**
	 * Stack variable to store environment-settings
	 *
	 */
	protected $envStack = array();

	/**
	 * Store relevant data - juat to be sure that nothing gets lost during FE-simulation
	 * and it really sucks that this is needed
	 *
	 * @see popEnv()
	 */
	public function pushEnv() {
		array_push($this->envStack, array('workDir' => getcwd(), 'BE_USER' => $GLOBALS['BE_USER'], 'TCA' => $GLOBALS['TCA']));
	}

	/**
	 * prepares Frontend-like-Rendering
	 * and it really sucks that this is needed
	 *
	 * @see pushEnv()
	 * @see popEnv()
	 */
	public function setEnv($backPath = '') {
		if ($this->BE_USER == NULL) {
			$this->initMyBE_USER();
		}
		if ($backPath && is_dir($backPath)) {
			chdir($backPath);
		}
		//$this->BE_USER_GLOBAL = $GLOBALS['BE_USER'];
		$GLOBALS['BE_USER'] = $this->BE_USER;
	}

	/**
	 * closes Frontend-like-Rendering
	 * and it also really sucks that this is needed
	 *
	 * @see setEnv()
	 */
	public function popEnv($curPath = '') {
		if (!is_array($this->envStack) || !count($this->envStack)) {
			return false;
		}
		$env = array_pop($this->envStack);

		if ($env['TCA'] && is_array($env['TCA'])) {
			$GLOBALS['TCA'] = $env['TCA'];
		}

		if ($env['BE_USER'] && is_object($env['BE_USER'])) {
			$GLOBALS['BE_USER'] = $env['BE_USER'];
		}

		if ($env['workDir'] && is_dir($env['workDir'])) {
			chdir($env['workDir']);
		}
	}

	/**
	 * reset/clear enableColumns - used to enable preview of access-restricted
	 * elements - use only with stored Env!!!!!
	 */
	public function resetEnableColumns($table, $newConf = NULL) {
		if (!is_array($this->envStack) || !count($this->envStack)) {
			return false;
		}
		if (!in_array($table, array_keys($GLOBALS['TCA']))) {
			return false;
		}
		$GLOBALS['TCA'][$table]['ctrl']['enablecolumns'] = $newConf;
		return true;
	}

	/**
	 * lazyload the feBEUSER
	 *
	 */
	protected function initMyBE_USER() {
		$this->BE_USER = t3lib_div::makeInstance('t3lib_tsfeBeUserAuth'); // New backend user object
		$this->BE_USER->userTS_dontGetCached = 1;
		$this->BE_USER->OS = TYPO3_OS;
		$this->BE_USER->setBeUserByUid($GLOBALS['BE_USER']->user['uid']);
		$this->BE_USER->unpack_uc('');
		if ($this->BE_USER->user['uid']) {
			$this->BE_USER->fetchGroupData();
			$GLOBALS['TSFE']->beUserLogin = 1;
		} else {
			$this->BE_USER = NULL;
			//die("Critical  ".__LINE__);
			// ??? $GLOBALS['TSFE']->beUserLogin = 0;
		}
	}

	/**
	 * Enables external debugging ...
	 *
	 */
	public function get_lastError() {
		return $this->lastError;
	}


	/**
	 * Recalculate BACKPATH for the current script-location,
	 * since the global BACKPATH might not be available or might be wrong
	 *
	 * @return string   the BACKPATH
	 */
	public static function getBackPath() {
		return preg_replace('/([^\/]+)\//', '../', str_replace(array(PATH_site, basename(PATH_thisScript)), array('', ''), PATH_thisScript));
	}

	/**
	 * Find extension BACKPATH,
	 * used to include resources from an extension (usually this is only used with imagemap_wizard)
	 * but it has a more generic functionality - YAGNI rules :P
	 *
	 * @param  string	$extKey - the source extension
	 * @return string	the Extensions BACKPATH
	 */
	public static function getExtBackPath($extKey = 'imagemap_wizard') {
		return self::getBackPath() . str_replace(PATH_site, '', t3lib_extMgm::extPath($extKey));
	}

	/**
	 * Get the value out of the Extension-Configuration determined by the submitted key
	 *
	 * @param  string	$confKey - the extension configuration key
	 * @param  string	$defaulr - default value which is used whenevery the extension configuration doesn't contain a valid value
	 * @return mixed	 either the config value or the default value
	 */
	public static function getExtConfValue($confKey, $default) {
		$conf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['imagemap_wizard']);
		return (is_array($conf) && in_array($confKey, array_keys($conf))) ? $conf[$confKey] : $default;
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_typo3env.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_typo3env.php']);
}


?>