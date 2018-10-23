<?php if ($showLabel && $showField): ?>
    <?php if (isset($options["row_start"])): ?>
        <?php if ($options["row_start"] === true): ?>
            <div class="row" style="padding-right:15px;padding-left:14px;">
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($options['wrapper'] !== false): ?>
        <?php if(isset($options['startExtraDiv'])):?>
            <div class="col-md-3 col-sm-5"> 
        <?php endif; ?>
        <div <?= $options['wrapperAttrs'] ?> >
        <?php endif; ?>
        <?php endif; ?>
            
        <?php if(isset($options['workOnSundayLabel'])): ?>
            <?php if ($options['workOnSundayLabel'] === true): ?> 
                <span class="can-label"><?= $options['workOnSundayText'] ?></span>
            <?php endif; ?>
        <?php endif; ?>  
                
        <?php if(isset($options['canStayOnsiteLabel'])): ?>
            <?php if ($options['canStayOnsiteLabel'] === true): ?> 
                <span class="can-label"><?= $options['canStayOnSiteLabelText'] ?></span>
            <?php endif; ?>
        <?php endif; ?>  
            
        <?php if (isset($options['showChildLabel'])): ?>
            <?php if ($options['canStayOnsiteLabel'] === true): ?> 
                <label class="extra-label text-transform-none text-normal cursor-pointer mr-rt-8" for="<?= $options['label_attr']['for'] ?>"><?= $options['childLabel'] ?></label>
            <?php endif; ?>
        <?php endif; ?>
                
        <?php if ($showField): ?>
            <?= Form::checkbox($name, $options['value'], $options['checked'], $options['attr']) ?>
            <?php include 'help_block.php' ?>
        <?php endif; ?>
            
        <?php if ($showLabel && $options['label'] !== false): ?>
            <?php if ($options['is_child']): ?>
                <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
            <?php else: ?>
                <?= Form::label($name, $options['label'], $options['label_attr']) ?>
            <?php endif; ?>
        <?php endif; ?>
                
        <?php if (isset($options['showExtraLabel'])): ?>
                <label class="extra-label text-transform-none text-normal cursor-pointer mr-rt-8" for="<?= $options['label_attr']['for'] ?>"><?= $options['extraLabel'] ?></label>
        <?php endif; ?>
                
        <?php include 'errors.php' ?>

        <?php if ($showLabel && $showField): ?>
            <?php if ($options['wrapper'] !== false): ?>
            </div>
                   <?php if(isset($options['endExtraDiv'])):?>
               </div> 
                   <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($options["row_end"])): ?>
            <?php if ($options["row_end"] === true): ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>   
<?php endif; ?>
