<?php if (isset($options["onlyToManager"])): ?>
    <?php if(($options["onlyToManager"] === true) && !(Auth::user()->Role->pluck("Slug")->contains(env("CUSTOMER_ROLE_SLUG")))): ?>
        <?php if ($showLabel && $showField): ?>
            <?php if (isset($options["row_start"])): ?>
                <?php if ($options["row_start"] === true): ?>
                    <div class="row" style="padding-right:15px;padding-left:15px">
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options['wrapper'] !== false): ?>
            <div <?= $options['wrapperAttrs'] ?> >
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($showLabel && $options['label'] !== false): ?>
            <?= Form::label($name, $options['label'], $options['label_attr']) ?>
            <?php if(isset($options["helpIcon"])): ?>   
                <?php if ($options['helpIcon'] === true): ?> 
                    <span class="fa fa-question-circle"></span>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($showField): ?>
            <?php if (isset($options["input-addon"])): ?>
                <?php if ($options["input-addon"] === true): ?>
                    <div class="input-group">
                <?php endif; ?>
            <?php endif; ?>
            <?= Form::input($type, $name, $options['value'], $options['attr']) ?>

            <?php if (isset($options['dateIcon'])): ?>
                <?php if ($options['dateIcon'] === true): ?>
                    <span class="fa fa-calendar form-control-feedback" style="right:1em"></span>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (isset($options["input-addon-content"])): ?>
                <div class="input-group-addon"><?= $options["input-addon-content"] ?></div>
            <?php endif; ?>
            <?php if (isset($options["input-addon"])): ?>
                <?php if ($options["input-addon"] === true): ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php include 'help_block.php' ?>
        <?php endif; ?>

        <?php include 'errors.php' ?>

        <?php if ($showLabel && $showField): ?>
            <?php if ($options['wrapper'] !== false): ?>
            </div>
            <?php endif; ?>
            <?php if (isset($options["row_end"])): ?>
                <?php if ($options["row_end"] === true): ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <?php if ($showLabel && $showField): ?>
        <?php if (isset($options["row_start"])): ?>
            <?php if ($options["row_start"] === true): ?>
                <div class="row" style="padding-right:15px;padding-left:15px">
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($options['wrapper'] !== false): ?>
            <div <?= $options['wrapperAttrs'] ?> >
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($showLabel && $options['label'] !== false): ?>
        <?= Form::label($name, $options['label'], $options['label_attr']) ?>
        <?php if (isset($options["helpIcon"])): ?>   
            <?php if ($options['helpIcon'] === true): ?> 
                <i class="fa fa-question-circle cursor-pointer help-icon" data-toggle="tooltip" data-original-title="<?=$options["helpText"]?>"></i>
            <?php endif; ?>
        <?php endif; ?>         
    <?php endif; ?>

    <?php if ($showField): ?>
        <?php if (isset($options["input-addon"])): ?>
            <?php if ($options["input-addon"] === true): ?>
                <div class="input-group">
            <?php endif; ?>
        <?php endif; ?>
        <?= Form::input($type, $name, $options['value'], $options['attr']) ?>

        <?php if (isset($options['dateIcon'])): ?>
            <?php if ($options['dateIcon'] === true): ?>
                <span class="fa fa-calendar form-control-feedback" style="right:1em"></span>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($options["input-addon-content"])): ?>
            <div class="input-group-addon"><?= $options["input-addon-content"] ?></div>
        <?php endif; ?>
        <?php if (isset($options["input-addon"])): ?>
            <?php if ($options["input-addon"] === true): ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php include 'help_block.php' ?>
    <?php endif; ?>

    <?php include 'errors.php' ?>

    <?php if ($showLabel && $showField): ?>
        <?php if ($options['wrapper'] !== false): ?>
            </div>
        <?php endif; ?>
        <?php if (isset($options["row_end"])): ?>
            <?php if ($options["row_end"] === true): ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
