<div id="panel_<?php echo $element->getId();?>" class="panel panel-default component">
    <div class="panel-heading groupTitle">
        <h3 class="panel-title">
            Group: <?php echo str_replace('_', ' ', $element->getId());?>
            <span id="<?php echo $element->getId();?>" class="glyphicon glyphicon-remove-circle glyphicon-remove-circle"></span>
        </h3>
    </div>
    <div class="panel-body">
        <div class="container-fluid">
            <?php
                include('editorComponents/groupColor.inc.php');
                include('editorComponents/groupCoords.inc.php');
                include('editorComponents/cmeo.inc.php');
                include('editorComponents/shadow.inc.php');
                include('editorComponents/stroke.inc.php');
            ?>
        </div>
    </div>
</div>

