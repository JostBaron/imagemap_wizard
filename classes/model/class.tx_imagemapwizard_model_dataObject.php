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
 * Class/Function used to access the given Map-Data within Backend-Forms
 *
 * @author    Tolleiv Nietsch <info@tolleiv.de>
 */

require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/model/class.tx_imagemapwizard_model_typo3env.php');
require_once(t3lib_extMgm::extPath('imagemap_wizard') . 'classes/model/class.tx_imagemapwizard_model_mapper.php');

class tx_imagemapwizard_model_dataObject {
    protected $row;
    protected $liveRow;
    protected $table;
    protected $mapField;
    protected $backPath;
    protected $modifiedFlag = FALSE;
    protected $fieldConf;

    /**
     *
     * @param $table
     * @param $field
     * @param $uid
     * @param $currentValue
     * @return unknown_type
     */
    public function __construct($table, $field, $uid, $currentValue = NULL) {
        if (!in_array($table, array_keys($GLOBALS['TCA']))) {
            throw new Exception('table (' . $table . ') not defined in TCA');
        }
        $this->table = $table;
        t3lib_div::loadTCA($this->table);
        if (!in_array($field, array_keys($GLOBALS['TCA'][$table]['columns']))) {
            throw new Exception('field (' . $field . ') unknow for table in TCA');
        }
        $this->mapField = $field;
        $this->row = t3lib_BEfunc::getRecordWSOL($table, intval($uid));
        if ($currentValue) {
            $this->useCurrentData($currentValue);
        }
        $this->liveRow = $this->row;
        t3lib_BEfunc::fixVersioningPid($table, $this->liveRow);
        $this->map = t3lib_div::makeInstance("tx_imagemapwizard_model_mapper")->map2array($this->getFieldValue($this->mapField));
        $this->backPath = tx_imagemapwizard_model_typo3env::getBackPath();
    }

    /**
     *
     * @param $field
     * @param $listNum
     * @return unknown_type
     */
    public function getFieldValue($field, $listNum = -1) {

        if (!is_array($this->row)) {
            return NULL;
        }
        $isFlex = $this->isFlexField($field);
        $parts = array();
        if ($isFlex) {
            $parts = explode(':', $field);
            $dbField = $parts[2];
        } else {
            $dbField = $field;
        }

        if (!array_key_exists($dbField, $this->row)) {
            return NULL;
        }

        $data = $this->row[$dbField];
        if ($isFlex) {
            $xml = t3lib_div::xml2array($data);
            $tools = t3lib_div::makeInstance('t3lib_flexformtools');
            $data = $tools->getArrayValueByPath($parts[3], $xml);

        }

        if ($listNum == -1) {
            return $data;
        } else {
            $tmp = preg_split('/,/', $data);
            return $tmp[$listNum];
        }
        return NULL;

    }

    /**
     * Fetches the first file reference from FAL
     *
     * @param string $field
     * @return string|NULL
     */
    public function getFalFieldValue($field) {
        $image = NULL;
        if (!is_array($this->row)) {
            return NULL;
        }

        /** @var t3lib_DB $db */
        $db = $GLOBALS['TYPO3_DB'];
        $row = $db->exec_SELECTgetSingleRow(
            'sys_file.identifier',
            'sys_file, sys_file_reference',
            'sys_file.uid = sys_file_reference.uid_local AND ' .
            'sys_file_reference.uid_foreign = ' . intval($this->row['uid']),
            '',
            'sorting_foreign ASC'
        );
        if (!$row) return NULL;
        $identifier = $row['identifier'];

        $someFileIdentifier = $identifier;
        /** @var \TYPO3\CMS\Core\Resource\StorageRepository $storageRepository */
        $storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
            'TYPO3\\CMS\\Core\\Resource\\StorageRepository'
        );
        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
        $storage = $storageRepository->findByUid(1);
        $file = $storage->getFile($someFileIdentifier);

        return $file->getPublicUrl();
    }

    /**
     *    Retrives current imagelocation - if multiple files are stored in the field only the first is recognized
     *
     * @param $abs
     * @return string
     */
    public function getImageLocation($abs = FALSE) {
        $location = '';
        $imageField = $this->determineImageFieldName();
        if ($this->table == 'tt_content' && $imageField == 'image' && t3lib_extMgm::isLoaded('dam_ttcontent') && t3lib_extMgm::isLoaded('dam')) {
            $imageField = 'tx_damttcontent_files';
            $damFiles = tx_dam_db::getReferencedFiles('tt_content', $this->getFieldValue('uid'), $imageField);
            $location = array_pop($damFiles['files']);
        } elseif (t3lib_div::compat_version('6.0')) {
            $location = $this->getFalFieldValue($imageField);
        } else {
            if ($this->isFlexField($imageField)) {
                $path = $this->getFieldConf('config/userImage/uploadfolder');
            } else {
                $path = $GLOBALS['TCA'][$this->table]['columns'][$imageField]['config']['uploadfolder'];
            }
            $location = $path . '/' . $this->getFieldValue($imageField, 0);
        }
        return ($abs ? PATH_site : $this->backPath) . $location;
    }

    /**
     *
     * @return boolean
     */
    public function hasValidImageFile() {
        return $this->getFieldValue('uid') &&
            is_file($this->getImageLocation(TRUE)) &&
            is_readable($this->getImageLocation(TRUE));
    }

    /**
     *    Renders the image within a frontend-like context
     *
     * @return string
     */
    public function renderImage() {
        $t3env = t3lib_div::makeInstance('tx_imagemapwizard_model_typo3env');
        if (!$t3env->initTSFE($this->getLivePid(), $GLOBALS['BE_USER']->workspace, $GLOBALS['BE_USER']->user['uid'])) {
            return 'Can\'t render image since TYPO3 Environment is not ready.<br/>Error was:' . $t3env->get_lastError();
        }
        $conf = array('table' => $this->table, 'select.' => array('uidInList' => $this->getLiveUid(), 'pidInList' => $this->getLivePid()));

        if (t3lib_extMgm::isLoaded('templavoila')) {
            require_once(t3lib_extMgm::extPath('templavoila') . 'pi1/class.tx_templavoila_pi1.php');
        }
        //render like in FE with WS-preview etc...
        $t3env->pushEnv();
        $t3env->setEnv(PATH_site);
        $t3env->resetEnableColumns('pages'); // enable rendering on access-restricted pages
        $t3env->resetEnableColumns('pages_language_overlay');
        $t3env->resetEnableColumns($this->table); // no fe_group, start/end, hidden restrictions needed :P
        $GLOBALS['TSFE']->cObj->LOAD_REGISTER(array('keepUsemapMarker' => '1'), 'LOAD_REGISTER');
        $result = $GLOBALS['TSFE']->cObj->CONTENT($conf);
        $t3env->popEnv();

        // extract the image
        $matches = array();
        if (!preg_match('/(<img[^>]+usemap="#[^"]+"[^>]*\/?>)/', $result, $matches)) {
            //TODO: consider to use the normal image as fallback here instead of showing an error-message
            return 'No Image rendered from TSFE. :(<br/>Has the page some kind of special doktype or has it access-restrictions?<br/>There are lot\'s of things which can go wrong since normally nobody creates frontend-output in the backend ;)<br/>Error was:' . $t3env->get_lastError();
        }
        $result = str_replace('src="', 'src="' . ($this->backPath), $matches[1]);
        return $result;
    }

    /**
     *  Renders a thumbnail with preconfiguraed dimensions
     *
     * @param $confKey
     * @param $defaultMaxWH
     * @return unknown_type
     */
    public function renderThumbnail($confKey, $defaultMaxWH) {
        $maxSize = t3lib_div::makeInstance('tx_imagemapwizard_model_typo3env')->getExtConfValue($confKey, $defaultMaxWH);
        $img = $this->renderImage();
        $matches = array();
        if (preg_match('/width="(\d+)" height="(\d+)"/', $img, $matches)) {
            $width = intval($matches[1]);
            $height = intval($matches[2]);
            if (($width > $maxSize) && ($width >= $height)) {
                $height = ($maxSize / $width) * $height;
                $width = $maxSize;
            } else if ($height > $maxSize) {
                $width = ($maxSize / $height) * $width;
                $height = $maxSize;
            }
            return preg_replace('/width="(\d+)" height="(\d+)"/', 'width="' . $width . '" height="' . $height . '"', $img);

        } else {
            return '';
        }
    }

    /**
     * Calculates the scale-factor which is required to scale down the imagemap to the thumbnail
     *
     * @param $confKey
     * @param $defaultMaxWH
     * @return float
     */
    public function getThumbnailScale($confKey, $defaultMaxWH) {
        $maxSize = t3lib_div::makeInstance('tx_imagemapwizard_model_typo3env')->getExtConfValue($confKey, $defaultMaxWH);
        $ret = 1;
        $img = $this->renderImage();
        $matches = array();
        if (preg_match('/width="(\d+)" height="(\d+)"/', $img, $matches)) {
            $width = intval($matches[1]);
            $height = intval($matches[2]);
            if (($width > $maxSize) && ($width >= $height)) {
                $ret = ($maxSize / $width);
            } else if ($height > $maxSize) {
                $ret = ($maxSize / $height);
            }
        }
        return $ret;
    }

    /**
     *
     * @param $template
     * @return string
     */
    public function listAreas($template = "") {
        if (!is_array($this->map["#"])) {
            return '';
        }
        $result = '';
        foreach ($this->map["#"] as $area) {
            $markers = array("##coords##" => $area["@"]["coords"], "##shape##" => ucfirst($area["@"]["shape"]), "##color##" => $this->attributize($area["@"]["color"]), "##link##" => $this->attributize($area["value"]), "##alt##" => $this->attributize($area["@"]["alt"]), "##attributes##" => $this->listAttributesAsSet($area));

            $result .= str_replace(array_keys($markers), array_values($markers), $template);

        }
        return $result;
    }

    /**
     *
     * @param $area
     * @return string
     */
    protected function listAttributesAsSet($area) {
        $relAttr = $this->getAttributeKeys();
        $ret = array();
        foreach ($relAttr as $key) {
            $ret[] = $key . ':\'' . $this->attributize(array_key_exists($key, $area["@"]) ? $area["@"][$key] : '') . '\'';
        }
        return implode(',', $ret);
    }

    /**
     *
     * @return string
     */
    public function emptyAttributeSet() {
        $relAttr = $this->getAttributeKeys();
        $ret = array();
        foreach ($relAttr as $key) {
            if ($key) {
                $ret[] = $key . ':\'\'';
            }
        }
        return implode(',', $ret);
    }

    /**
     *
     * @param $v
     * @return string
     */
    protected function attributize($v) {

        $attr = preg_replace('/([^\\\\])\\\\\\\\\'/', '\1\\\\\\\\\\\'', str_replace('\'', '\\\'', $v));

        if ($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] != 'utf-8') {
            $attr = '\' + jQuery.base64Decode(\'' . base64_encode($attr) . '\') + \'';
        }

        return $attr;
    }

    /**
     *
     * @return array
     */
    public function getAttributeKeys() {
        $keys = t3lib_div::trimExplode(',', tx_imagemapwizard_model_typo3env::getExtConfValue('additionalAttributes', ''));
        $keys = array_diff($keys, array('alt', 'href', 'shape', 'coords'));
        $keys = array_map("strtolower", $keys);
        return array_filter($keys);
    }

    /**
     *
     * @return int
     */
    protected function getLivePid() {
        return $this->row['pid'] > 0 ? $this->row['pid'] : $this->liveRow['pid'];
    }

    /**
     *
     * @return int
     */
    protected function getLiveUid() {
        return (($GLOBALS['BE_USER']->workspace===0) || ($this->row['t3ver_oid'] == 0)) ? $this->row['uid'] : $this->row['t3ver_oid'];
    }

    /**
     *
     * @return string
     */
    protected function determineImageFieldName() {
        $imgField = $this->getFieldConf('config/userImage/field') ? $this->getFieldConf('config/userImage/field') : 'image';
        if ($this->isFlexField($this->mapField)) {
            $imgField = preg_replace('/\/[^\/]+\/(v\S+)$/', '/' . $imgField . '/\1', $this->mapField);
        }
        return $imgField;
    }

    /**
     *
     * @return string
     */
    public function getTablename() {
        return $this->table;
    }

    /**
     *
     * @return string
     */
    public function getFieldname() {
        return $this->mapField;
    }

    /**
     *
     * @return array
     */
    public function getRow() {
        return $this->row;
    }

    /**
     *
     * @return int
     */
    public function getUid() {
        return $this->row['uid'];
    }

    /**
     *
     * @param $value
     * @return void
     */
    public function useCurrentData($value) {
        $cur = $this->getCurrentData();
        if (!t3lib_div::makeInstance("tx_imagemapwizard_model_mapper")->compareMaps($cur, $value)) {
            $this->modifiedFlag = TRUE;
        }

        if ($this->isFlexField($this->mapField)) {
            $tools = t3lib_div::makeInstance('t3lib_flexformtools');
            $parts = explode(':', $this->mapField);
            $data = t3lib_div::xml2array($this->row[$parts[2]]);
            $tools->setArrayValueByPath($parts[3], $data, $value);
            $this->row[$parts[2]] = $tools->flexArray2Xml($data);
        } else {
            $this->row[$this->mapField] = $value;
        }
    }

    /**
     *
     * @return string
     */
    public function getCurrentData() {
        if ($this->isFlexField($this->mapField)) {
            $tools = t3lib_div::makeInstance('t3lib_flexformtools');
            $parts = explode(':', $this->mapField);
            $data = t3lib_div::xml2array($this->row[$parts[2]]);
            return $tools->getArrayValueByPath($parts[3], $data);
        } else {
            return $this->row[$this->mapField];
        }
    }

    /**
     *
     * @return boolean
     */
    public function hasDirtyState() {
        return $this->modifiedFlag;
    }

    /**
     *
     * @param $cfg
     * @return void
     */
    public function setFieldConf($cfg) {
        $this->fieldConf = $cfg;
    }

    /**
     *
     * @param $subKey
     * @return array
     */
    public function getFieldConf($subKey = NULL) {
        if ($subKey == NULL) {
            return $this->fieldConf;
        }
        $tools = t3lib_div::makeInstance('t3lib_flexformtools');
        return $tools->getArrayValueByPath($subKey, $this->fieldConf);
    }

    /**
     *
     * @param $field
     * @return boolesan
     */
    protected function isFlexField($field) {
        $theField = $field;
        if (stristr($field, ':')) {
            $parts = explode(':', $field);
            $theField = $parts[2];
        }
        return ($GLOBALS['TCA'][$this->table]['columns'][$theField]['config']['type'] == 'flex');
    }

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_dataObject.php']) {
    include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagemap_wizard/classes/model/class.tx_imagemapwizard_model_dataObject.php']);
}

?>
