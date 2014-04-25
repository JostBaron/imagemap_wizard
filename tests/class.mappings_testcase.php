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

class mappings_testcase extends tx_phpunit_testcase {

	/**
	 * @var $mapper tx_imagemapwizard_model_mapper
	 */
	protected $mapper;

	function test_creatingEmptyMap() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->never())->method('typoLink');

		$supposedOutput = '';
		$this->assertEquals($supposedOutput,$this->mapper->generateMap($cObj,'testname'),'Empty Map is not created as supposed');
		$this->assertEquals($supposedOutput,$this->mapper->generateMap($cObj,'testname',array()),'Empty Map is not created as supposed');
	}

	function test_emptyMapNameDoesnTHurt() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$input = '<map></map>';
		$this->assertEquals('',$this->mapper->generateMap($cObj,'',$input),'Empty Map-Name inputs are not processed as supposed');
	}

	function test_creatingValidMapNames() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->never())->method('typoLink');

		$strings = array('test name','test näme','ÄÖÜ..','1234','おはようございます');

		$regExAttr = '/^[a-zA-Z][a-zA-Z0-9\-_]+[a-zA-Z0-9]$/i';
		foreach($strings as $key=>$string) {
			$this->assertEquals(1,preg_match($regExAttr,$this->mapper->createValidNameAttribute($string)),'Attribute ('.$key.') is not cleaned as supposed...['.$this->mapper->createValidNameAttribute($string).']');
		}
	}

	function test_creatingSimpleRectMap() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->atLeastOnce())->method('typoLink')->will($this->returnValue('<a href="http://www.foo.org" title="tt">text</a>'));

		$input = '<map><area shape="rect">1</area></map>';
		$output = '<map name="test"><area href="http://www.foo.org" title="tt" shape="rect" /></map>';
		$this->assertEquals($output,$this->mapper->generateMap($cObj,'test',$input),'Generator Output looks not as supposed');
	}
	function test_creatingMapGeneratorKeepsIndividualAttributes() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->atLeastOnce())->method('typoLink')->will($this->returnValue('<a href="http://www.foo.org" title="tt">text</a>'));

		$input = '<map><area shape="rect" title="individual title" xyz="1">1</area></map>';
		$output = '<map name="test"><area href="http://www.foo.org" title="individual title" shape="rect" xyz="1" /></map>';
		$this->assertEquals($output,$this->mapper->generateMap($cObj,'test',$input),'Individual Attributes are lost after Generation');
	}

	function test_creatingMapRemovesEmptyAttributes() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->atLeastOnce())->method('typoLink')->will($this->returnValue('<a href="http://www.foo.org" title="tt">text</a>'));

		$input = '<map><area shape="rect" title="individual title" xyz="">1</area></map>';
		$output = '<map name="test"><area href="http://www.foo.org" title="individual title" shape="rect" /></map>';
		$this->assertEquals($output,$this->mapper->generateMap($cObj,'test',$input),'Empty Attribute should be removed during Generation');
	}

	function test_creatingMapGeneratorAcceptsAttributeWhitelist() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->atLeastOnce())->method('typoLink')->will($this->returnValue('<a href="http://www.foo.org" title="tt">text</a>'));

		$whitelist = array('href','shape');

		$input = '<map><area shape="rect" title="individual title" xyz="1">1</area></map>';
		$output = '<map name="test"><area href="http://www.foo.org" shape="rect" /></map>';
		$this->assertEquals($output,$this->mapper->generateMap($cObj,'test',$input,$whitelist),'Individual Attributes are lost after Generation');
	}

	function test_creatingMapUsingHrefAttrIfNoValueExists() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$cObj->expects($this->atLeastOnce())->method('typoLink')->will($this->returnValue('<a href="http://www.foo.org">text</a>'));

		//stupid href-value but this proveds that the typoLink-function is really used
		$input = '<map><area href="1" shape="rect" /></map>';
		$output = '<map name="test"><area href="http://www.foo.org" shape="rect" /></map>';
		$this->assertEquals($output,$this->mapper->generateMap($cObj,'test',$input),'Href-Attribute is not recognized for the area-link creation.');
	}

	// due to issue 2525
	function test_xhtmlSwitchWorks() {
		$cObj = $this->getMock('tslib_cObj', array('typoLink','LOAD_REGISTER'));
		$input = '<map><area href="1" shape="rect" /></map>';
		$name = "testname";
		$htmlOutput = '<map name="'.$name.'"><area href="1" shape="rect" /></map>';
		$xhtmlOutput = '<map  id="'.$name.'" name="'.$name.'"><area href="1" shape="rect" /></map>';
		$this->assertEquals(true,$this->mapper->compareMaps($htmlOutput,$this->mapper->generateMap($cObj,$name,$input,array(),false)),' HTML mapname is not generated as supposed');
		$this->assertEquals(true,$this->mapper->compareMaps($xhtmlOutput,$this->mapper->generateMap($cObj,$name,$input,array(),true)),' XHTML mapname is not generated as supposed');
	}


	function test_simpleComparingWorks() {
		$map1 = '<map><area>1</area></map>';
		$map2 = '<map><area>2</area></map>';
		$this->assertEquals(true,$this->mapper->compareMaps($map1,$map1),'Equal maps are not recognized when compared...');
		$this->assertEquals(false,$this->mapper->compareMaps($map1,$map2),'Different maps are not recognized when compared...');
	}

	function test_complexerComparingWithVariousAttributeOrderWorks() {
		$map1 = '<map><area xxx="abc" color="green">1</area></map>';
		$map2 = '<map><area color="green" xxx="abc">1</area></map>';
		$this->assertEquals(true,$this->mapper->compareMaps($map1,$map1),'Equal maps are not recognized when compared...');
	}

	function test_compairingDifferentStructures() {
		$map1 = '<map></map>';
		$map2 = '<map><area xxx="abc" color="green">1</area></map>';
		$map3 = '<map attr="value" />';
		$this->assertEquals(false,$this->mapper->compareMaps($map1,$map2),'Different structured maps are not processed as supposed');
		$this->assertEquals(false,$this->mapper->compareMaps($map1,$map3),'Different structured maps are not processed as supposed');
		$this->assertEquals(false,$this->mapper->compareMaps($map2,$map3),'Different structured maps are not processed as supposed');
	}

	function test_detectEmptyMaps() {
		$map1 = '<map></map>';
		$map2 = '<map><area xxx="abc" color="green">1</area></map>';
		$map3 = '<map attr="value" />';
		$this->assertEquals(true,$this->mapper->isEmptyMap($map1),'Empty map1 is  not recognized.');
		$this->assertEquals(false,$this->mapper->isEmptyMap($map2),'Map2 is  recognized to be empty by mistake.');
		$this->assertEquals(true,$this->mapper->isEmptyMap($map3),'Empty map3 is  not recognized.');
		$this->assertEquals(true,$this->mapper->isEmptyMap(''),'Empty string is  not recognized.');
	}

	function setUp() {
		require_once(t3lib_extMgm::extPath('imagemap_wizard').'classes/model/class.tx_imagemapwizard_model_mapper.php');
		$this->mapper = t3lib_div::makeInstance('tx_imagemapwizard_model_mapper');
	}
	function tearDown() {
		unset($this->mapper);
	}
}


?>
