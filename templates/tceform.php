<?php
$this->addExternalJS("templates/js/jquery-1.4.min.js");
$this->addExternalJS("templates/js/jquery-ui-1.7.2.custom.min.js");
$this->addExternalJS("templates/js/jquery.base64.js");
$this->addExternalJS("templates/js/wizard.all.js.ycomp.js");

$existingFields = $this->data->listAreas("\tcanvasObject.addArea(new area##shape##Class(),'##coords##','##alt##','##link##','##color##',0);\n");

$this->addInlineJS('
jQuery.noConflict();

function imagemapwizard_valueChanged(field) {
    jQuery.ajaxSetup({
        url: "'.$this->getAjaxURL('wizard.php').'",
        global: false,
        type: "POST",
        success: function(data, textStatus) {
            if(textStatus==\'success\') {
                jQuery("#'.$this->getId().'").html(data);
            }
        },
        data: { context:"tceform",
                ajax: "1",
                formField:field.name,
                value:field.value,
                table:"'.$this->data->getTablename().'",
                field:"'.$this->data->getFieldname().'",
                uid:"'.$this->data->getUid().'",
                config:"'.addslashes(serialize($this->data->getFieldConf())).'"
        }
    });
    jQuery.ajax();
}
');
$additionalWizardConf = array('fieldChangeFunc'=>array('imagemapwizard_valueChanged(field);'));
?>
<div id="<?php echo $this->getId(); ?>" style="position:relative">

    <?php
        ob_start();
    ?>
    <div class="imagemap_wiz" style="padding:5px;overflow:hidden;position:relative">
        <div id="<?php echo $this->getId(); ?>-canvas" style="position:relative;top:5px;left:5px;overflow:hidden;">
        <?php
            echo $this->data->renderThumbnail('previewImageMaxWH',200);
        ?>
        </div>
    </div>
    <?php
        $imagepreview = ob_get_contents();
        ob_end_clean();
        echo $this->form->renderWizards(array($imagepreview,''),$this->wizardConf,$this->data->getTablename(),$this->data->getRow(),$this->data->getFieldname(),$additionalWizardConf,$this->formName,array(),1)
    ?>

    <?php
        if($this->data->hasDirtyState()) {
            echo '<div class="imagemap_wiz_message" style="display:none;width:170px;height:70px;padding:20px 40px 10px 40px;position:absolute;z-index:999;background: url('.$this->getTplSubpath().'img/form-tooltip.png) no-repeat;">';
            $this->getLL('form.is_dirty',1);
            echo '<div class="imagemap_wiz_message_close" style="display:block;position:absolute;right:15px;top:15px;cursor:pointer">[x]</div></div>';
        }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function(){
        canvasObject = new previewCanvasClass();
        canvasObject.init("<?php echo $this->getId(); ?>-canvas","<?php echo $this->data->getThumbnailScale('previewImageMaxWH',200) ?>");
        <?php echo $existingFields; ?>
        jQuery(".imagemap_wiz_message").css({top: (canvasObject.getMaxH()/2-35)+"px", left: "20px"}).animate({left: "60px",opacity: "show"}, 750).animate({left: "60px"}, 6000).animate({left: "20px", opacity: "hide"}, 750);
        jQuery(".imagemap_wiz_message_close").click(function() {
            jQuery(".imagemap_wiz_message").animate({left: "20px", opacity: "hide"}, {duration:250, queue:false});
        });
    });
    </script>
    <input type="hidden" name="<?php echo $this->formName; ?>" value="<?php echo htmlspecialchars($this->data->getCurrentData()); ?>" />
</div>