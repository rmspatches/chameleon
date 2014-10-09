<div class="panel panel-default" style="min-height: 350px;">
    <div class="panel-heading globalsTitle">
        <h3 class="panel-title">
                Global settings for template:
        </h3>
    </div>
    <div id="globalsBody" class="panel-body">
        <div class="container-fluid">
            <div class="row">
                <label class="col-md-4">Name:</label>
                <div>
                    <input type="text" disabled="disabled" value="<?php echo $this->name;?>">
                </div>
            </div>
                <?php include('editorComponents/globalDimensions.inc.php'); ?>
            <div class="row">
                <label class="col-md-4">Approx. size:</label>
                <div>
                    <input type="text" disabled="disabled" value="<?php echo $this->fileSize;?> kB">
                </div>
            </div>
            <?php
                include('editorComponents/globalColor.inc.php');
                include('editorComponents/globalFont.inc.php');
            ?>
            <div class="row">
                <label class="col-md-4" style="height: <?php echo count($this->activeCategories) * 22?>px;">
                    Categories:
                    <button id="editCategoriesEditor" type="button" class="btn btn-xs" data-toggle="modal" data-target="#categorySelect"
                            style="background-color: #333333; color: #FFFFFF;">
                        <span class="glyphicon glyphicon-pencil"></span>
                    </button>
                </label>
                <div>
                    <?php
                        foreach($this->activeCategories as $activeCategory):
                    ?>
                    <input type="text" disabled="disabled" value="<?php echo $activeCategory['name'];?>">
                    <?php
                        endforeach;
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
