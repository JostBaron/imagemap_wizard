<?php
$this->addExternalJS("templates/js/jquery-1.4.min.js");
$this->addExternalJS("templates/js/jquery-ui-1.7.2.custom.min.js");
$this->addExternalJS("templates/js/jquery.base64.js");
$this->addExternalJS("templates/js/wizard.all.js.ycomp.js");
$this->addExternalCSS("templates/default.css");

$existingFields = $this->data->listAreas("\tcanvasObject.addArea(new area##shape##Class(),'##coords##','##alt##','##link##','##color##',0,{##attributes##});\n");

$this->addInlineJS('
var canvaseObject;
var scaleFactor = 1;
var defaultAttributeset = {'.$this->data->emptyAttributeSet().'};
jQuery.noConflict();
jQuery(document).ready(function(){
    canvasObject = new canvasClass();
    canvasObject.init("canvas","picture","areaForms");
	'.$existingFields.'

    scaleFactor = canvasObject.initializeScaling('.t3lib_div::makeInstance('tx_imagemapwizard_model_typo3env')->getExtConfValue('imageMaxWH',700).');
    canvasObject.setScale(scaleFactor);        // todo: store last used scale per Image...

    if(scaleFactor < 1) {
        jQuery("#magnify > .zout").hide();
    } else {
        jQuery("#magnify > .zin").hide();
        jQuery("#magnify > .zout").hide();
    }


    jQuery("#addRect").click(function(event) {
        canvasObject.addArea(new areaRectClass(),\'\',\'\',\'\',\'\',1,defaultAttributeset);
        return false;
    });
    jQuery("#addPoly").click(function(event) {
        canvasObject.addArea(new areaPolyClass(),\'\',\'\',\'\',\'\',1,defaultAttributeset);
        return false;
    });
    jQuery("#addCirc").click(function(event) {
        canvasObject.addArea(new areaCircleClass(),\'\',\'\',\'\',\'\',1,defaultAttributeset);
        return false;
    });
    jQuery("#submit").click(function(event) {
    	setValue("<map>" + canvasObject.persistanceXML() + "\n</map>");
    	close();
    });
    jQuery("#canvas").mousedown(function(e){
        return canvasObject.mousedown(e);
    });
    jQuery(document).mouseup(function(e){
        return canvasObject.mouseup(e);
    });
    jQuery(document).mousemove(function(e){
        return canvasObject.mousemove(e);
    });
    jQuery(document).dblclick(function(e){
        return canvasObject.dblclick(e);
    });
    jQuery("#magnify > .zin").click(function(event){
        canvasObject.setScale(1);
        jQuery(this).hide();
        jQuery("#magnify > .zout").show();
    });
    jQuery("#magnify > .zout").click(function(event){
        canvasObject.setScale(scaleFactor);
        jQuery(this).hide();
        jQuery("#magnify > .zin").show();
    });
});
');

 ?>
<div id="root">
    <div id="pic">
        <div id="magnify"><?php echo $this->getIcon("gfx/zoom_in.gif","class=\"zin\" alt=\"".$this->getLL('imagemap_wizard.form.zoomin')."\" title=\"".$this->getLL('imagemap_wizard.form.zoomin')."\""); echo $this->getIcon("gfx/zoom_out.gif","class=\"zout\" alt=\"".$this->getLL('imagemap_wizard.form.zoomout')."\" title=\"".$this->getLL('imagemap_wizard.form.zoomout')."\""); ?></div>
        <div id="picture">
            <div id="image"><?php echo $this->data->renderImage(); ?></div>
            <div id="canvas" class="canvas"><!-- --></div>
        </div>
    </div>
	<div id="actions">
        <input type="submit" id="addRect" value="<?php $this->getLL('imagemap_wizard.form.addrect',1); ?>" />
        <input type="submit" id="addCirc" value="<?php $this->getLL('imagemap_wizard.form.addcirc',1); ?>" />
        <input type="submit" id="addPoly" value="<?php $this->getLL('imagemap_wizard.form.addpoly',1); ?>" />
        <input type="submit" id="submit" value="<?php $this->getLL('imagemap_wizard.form.submit',1); ?>" />
    </div>
    <div id="areaForms">
        <div id="rectForm" class="areaForm bgColor5">
            <div id="MAPFORMID_main" class="basicOptions">
            	<div class="colorPreview ptr"><div><!-- --></div></div>
                <label for="MAPFORMID_label"><?php $this->getLL('imagemap_wizard.form.area.label',1); ?>:</label><input type="text" id="MAPFORMID_label" value="..." />
            	<label for="MAPFORMID_link"><?php $this->getLL('imagemap_wizard.form.area.link',1); ?>:</label><input type="text" id="MAPFORMID_link" value="..." /> <?php  echo $this->linkWizardIcon("MAPFORMID_linkwizard","MAPFORMID_link","MAPAREAVALUE_URL","canvasObject.triggerAreaLinkUpdate(\"OBJID\")"); ?>
            	<?php echo $this->getIcon("gfx/button_up.gif","id=\"MAPFORMID_up\" alt=\"".$this->getLL('imagemap_wizard.form.area.up')."\" title=\"".$this->getLL('imagemap_wizard.form.area.up')."\" class=\"ptr sortbtn upbtn\""); ?>
            	<?php echo $this->getIcon("gfx/button_down.gif","id=\"MAPFORMID_down\" alt=\"".$this->getLL('imagemap_wizard.form.area.down')."\" title=\"".$this->getLL('imagemap_wizard.form.area.down')."\" class=\"ptr sortbtn downbtn\""); ?>
                <?php echo $this->getIcon("gfx/undo.gif","id=\"MAPFORMID_undo\" alt=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" title=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" class=\"ptr undo\""); ?>
                <?php echo $this->getIcon("gfx/redo.gif","id=\"MAPFORMID_redo\" alt=\"".$this->getLL('imagemap_wizard.form.area.redo')."\" title=\"".$this->getLL('imagemap_wizard.form.area redo')."\" class=\"ptr redo\""); ?>
            	<?php echo $this->getIcon("gfx/garbage.gif","id=\"MAPFORMID_del\" class=\"ptr\" alt=\"".$this->getLL('imagemap_wizard.form.area.remove')."\" title=\"".$this->getLL('imagemap_wizard.form.area.remove')."\""); ?>
            	<div class="arrow exp ptr"><?php echo $this->getIcon("gfx/pil2down.gif","class=\"ptr expUpDown down\" alt=\"".$this->getLL('imagemap_wizard.form.area.expand')."\" title=\"".$this->getLL('imagemap_wizard.form.area.expand')."\""); echo $this->getIcon("gfx/pil2up.gif","class=\"ptr expUpDown up\" alt=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\" title=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\""); ?></div>
            </div>
            <div id="MAPFORMID_more" class="moreOptions">
                <div class="halfLine">
            	    <div id="MAPFORMID_color" class="colors"><div class="colorBox"><div><!-- --></div></div><div class="colorPicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_stroke" class="colors"><div class="strokeBox"><div><!-- --></div></div><div class="strokePicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_attributes" class="attributes"><?php echo $this->renderAttributesTemplate('<div class="attribute"><label for="MAPFORMID_ATTRNAME">ATTRLABEL:</label><input type="text" id="MAPFORMID_ATTRNAME" value="..." /><br class="cc" /></div>'); ?></div>
                </div>
                <div class="positionOptions halfLine"><?php echo $this->getIcon("gfx/refresh_n.gif","id=\"MAPFORMID_upd\" class=\"ptr refresh\" alt=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\" title=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\""); ?><label for="MAPFORMID_x1" class="XYlabel XYlabel-first">X1:</label><input type="text" class="formCoord" id="MAPFORMID_x1" value="x" /><label for="MAPFORMID_y1" class="XYlabel">Y1:</label><input type="text" class="formCoord" id="MAPFORMID_y1" value="y" /><br class="cc"/><label for="MAPFORMID_x2" class="XYlabel XYlabel-first">X2:</label><input type="text" class="formCoord" id="MAPFORMID_x2" value="x" /><label for="MAPFORMID_y2" class="XYlabel">Y2:</label><input type="text" class="formCoord" id="MAPFORMID_y2" value="y" /><div class="cc"><!-- --></div></div>
                <div class="cc"><!-- --></div>
            </div>
        </div>
        <div id="circForm" class="areaForm bgColor5">
            <div id="MAPFORMID_main" class="basicOptions">
            	<div class="colorPreview ptr"><div><!-- --></div></div>
                <label for="MAPFORMID_label"><?php $this->getLL('imagemap_wizard.form.area.label',1); ?>:</label><input type="text" id="MAPFORMID_label" value="..." />
            	<label for="MAPFORMID_link"><?php $this->getLL('imagemap_wizard.form.area.link',1); ?>:</label><input type="text" id="MAPFORMID_link" value="..." /> <?php  echo $this->linkWizardIcon("MAPFORMID_linkwizard","MAPFORMID_link","MAPAREAVALUE_URL","canvasObject.triggerAreaLinkUpdate(\"OBJID\")"); ?>
            	<?php echo $this->getIcon("gfx/button_up.gif","id=\"MAPFORMID_up\" alt=\"".$this->getLL('imagemap_wizard.form.area.up')."\" title=\"".$this->getLL('imagemap_wizard.form.area.up')."\" class=\"ptr sortbtn upbtn\""); ?>
                <?php echo $this->getIcon("gfx/button_down.gif","id=\"MAPFORMID_down\" alt=\"".$this->getLL('imagemap_wizard.form.area.down')."\" title=\"".$this->getLL('imagemap_wizard.form.area.down')."\" class=\"ptr sortbtn downbtn\""); ?>
                <?php echo $this->getIcon("gfx/undo.gif","id=\"MAPFORMID_undo\" alt=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" title=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" class=\"ptr undo\""); ?>
                <?php echo $this->getIcon("gfx/redo.gif","id=\"MAPFORMID_redo\" alt=\"".$this->getLL('imagemap_wizard.form.area.redo')."\" title=\"".$this->getLL('imagemap_wizard.form.area redo')."\" class=\"ptr redo\""); ?>
            	<?php echo $this->getIcon("gfx/garbage.gif","id=\"MAPFORMID_del\" class=\"ptr\" alt=\"".$this->getLL('imagemap_wizard.form.area.remove')."\" title=\"".$this->getLL('imagemap_wizard.form.area.remove')."\""); ?>
            	<div class="arrow exp ptr"><?php echo $this->getIcon("gfx/pil2down.gif","class=\"ptr expUpDown down\" alt=\"".$this->getLL('imagemap_wizard.form.area.expand')."\" title=\"".$this->getLL('imagemap_wizard.form.area.expand')."\""); echo $this->getIcon("gfx/pil2up.gif","class=\"ptr expUpDown up\" alt=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\" title=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\""); ?></div>
            </div>
            <div id="MAPFORMID_more" class="moreOptions">
                <div class="halfLine">
            	    <div id="MAPFORMID_color" class="colors"><div class="colorBox"><div><!-- --></div></div><div class="colorPicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_stroke" class="colors"><div class="strokeBox"><div><!-- --></div></div><div class="strokePicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_attributes" class="attributes"><?php echo $this->renderAttributesTemplate('<div class="attribute"><label for="MAPFORMID_ATTRNAME">ATTRLABEL:</label><input type="text" id="MAPFORMID_ATTRNAME" value="..." /><br class="cc" /></div>'); ?></div>
                </div>
                <div class="positionOptions halfLine"><?php echo $this->getIcon("gfx/refresh_n.gif","id=\"MAPFORMID_upd\" class=\"ptr refresh\" alt=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\" title=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\""); ?><label for="MAPFORMID_x" class="XYlabel XYlabel-first">X:</label><input type="text" class="formCoord" id="MAPFORMID_x" value="x" /><label for="MAPFORMID_y1" class="XYlabel">Y:</label><input type="text" class="formCoord" id="MAPFORMID_y" value="y" /><br class="cc"/><label for="MAPFORMID_radius" class="XYlabel XYlabel-first">R:</label><input type="text" class="formCoord" id="MAPFORMID_radius" value="r" /><div class="cc"><!-- --></div></div>
                <div class="cc"><!-- --></div>
            </div>
        </div>
        <div id="polyForm" class="areaForm bgColor5">
            <div id="MAPFORMID_main" class="basicOptions">
            	<div class="colorPreview ptr"><div><!-- --></div></div>
                <label for="MAPFORMID_label"><?php $this->getLL('imagemap_wizard.form.area.label',1); ?>:</label><input type="text" id="MAPFORMID_label" value="..." />
            	<label for="MAPFORMID_link"><?php $this->getLL('imagemap_wizard.form.area.link',1); ?>:</label><input type="text" id="MAPFORMID_link" value="..." /> <?php  echo $this->linkWizardIcon("MAPFORMID_linkwizard","MAPFORMID_link","MAPAREAVALUE_URL","canvasObject.triggerAreaLinkUpdate(\"OBJID\")"); ?>
            	<?php echo $this->getIcon("gfx/button_up.gif","id=\"MAPFORMID_up\" alt=\"".$this->getLL('imagemap_wizard.form.area.up')."\" title=\"".$this->getLL('imagemap_wizard.form.area.up')."\" class=\"ptr sortbtn upbtn\""); ?>
            	<?php echo $this->getIcon("gfx/button_down.gif","id=\"MAPFORMID_down\" alt=\"".$this->getLL('imagemap_wizard.form.area.down')."\" title=\"".$this->getLL('imagemap_wizard.form.area.down')."\" class=\"ptr sortbtn downbtn\""); ?>
                <?php echo $this->getIcon("gfx/undo.gif","id=\"MAPFORMID_undo\" alt=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" title=\"".$this->getLL('imagemap_wizard.form.area.undo')."\" class=\"ptr undo\""); ?>
                <?php echo $this->getIcon("gfx/redo.gif","id=\"MAPFORMID_redo\" alt=\"".$this->getLL('imagemap_wizard.form.area.redo')."\" title=\"".$this->getLL('imagemap_wizard.form.area redo')."\" class=\"ptr redo\""); ?>
            	<?php echo $this->getIcon("gfx/garbage.gif","id=\"MAPFORMID_del\" class=\"ptr\" alt=\"".$this->getLL('imagemap_wizard.form.area.remove')."\" title=\"".$this->getLL('imagemap_wizard.form.area.remove')."\""); ?>
                <?php echo $this->getIcon("gfx/add.gif","id=\"MAPFORMID_add\" alt=\"".$this->getLL('imagemap_wizard.form.poly.add')."\" title=\"".$this->getLL('imagemap_wizard.form.poly.add')."\" class=\"ptr add\""); ?>
            	<div class="arrow exp ptr"><?php echo $this->getIcon("gfx/pil2down.gif","class=\"ptr expUpDown down\" alt=\"".$this->getLL('imagemap_wizard.form.area.expand')."\" title=\"".$this->getLL('imagemap_wizard.form.area.expand')."\""); echo $this->getIcon("gfx/pil2up.gif","class=\"ptr expUpDown up\" alt=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\" title=\"".$this->getLL('imagemap_wizard.form.area.collapse')."\""); ?></div>
            </div>
            <div id="MAPFORMID_more" class="moreOptions">
                <div class="halfLine">
            	    <div id="MAPFORMID_color" class="colors"><div class="colorBox"><div><!-- --></div></div><div class="colorPicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_stroke" class="colors"><div class="strokeBox"><div><!-- --></div></div><div class="strokePicker"><!-- --></div><div class="cc""><!-- --></div></div>
                    <div id="MAPFORMID_attributes" class="attributes"><?php echo $this->renderAttributesTemplate('<div class="attribute"><label for="MAPFORMID_ATTRNAME">ATTRLABEL:</label><input type="text" id="MAPFORMID_ATTRNAME" value="..." /><br class="cc" /></div>'); ?></div>
                </div>
                <div class="positionOptions halfLine"><?php echo $this->getIcon("gfx/refresh_n.gif","id=\"MAPFORMID_upd\"class=\"ptr refresh\" alt=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\" title=\"".$this->getLL('imagemap_wizard.form.area.refresh')."\""); ?>POLYCOORDS<div class="cc"><!-- --></div></div>
                <div class="cc"><!-- --></div>
            </div>
        </div>
        <div id="polyCoords" class="noIdWrap">
            <label for="MAPFORMID_xvN" class="XYlabel">XvN:</label><input type="text" class="formCoord" id="MAPFORMID_xvN" value="vX" /><label for="MAPFORMID_yvN" class="XYlabel">YvN:</label><input type="text" class="formCoord" id="MAPFORMID_yvN" value="vY" />
             <?php echo $this->getIcon("gfx/arrowup.png","id=\"MAPFORMID_beforevN\" alt=\"".$this->getLL('imagemap_wizard.form.poly.addEdgeBefore')."\" title=\"".$this->getLL('imagemap_wizard.form.poly.addEdgeBefore')."\" class=\"coordOpt addCoord ptr\""); ?>
             <?php echo $this->getIcon("gfx/arrowdown.png","id=\"MAPFORMID_aftervN\" alt=\"".$this->getLL('imagemap_wizard.form.poly.addEdgeAfter')."\" title=\"".$this->getLL('imagemap_wizard.form.poly.addEdgeAfter')."\" class=\"coordOpt addCoord ptr\""); ?>
             <?php echo $this->getIcon("gfx/close_gray.gif","id=\"MAPFORMID_rmvN\" alt=\"".$this->getLL('imagemap_wizard.form.poly.removeEdge')."\" title=\"".$this->getLL('imagemap_wizard.form.poly.removeEdge')."\" class=\"coordOpt rmCoord ptr\""); ?><br class="cc" />
        </div>
    </div>
	<span class="cc"><!-- --></span>
</div>
