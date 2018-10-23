<?php if (isset($options["onlyToManager"])): ?>
    <?php if (($options["onlyToManager"] === true) && !(Auth::user()->Role->pluck("Slug")->contains(env("CUSTOMER_ROLE_SLUG")))): ?>
        <?php if ($showLabel && $showField): ?>
            <?php if (isset($options["row_start"])): ?>
                <?php if ($options["row_start"] === true): ?>
                    <div class="row" style="padding-right:15px;padding-left:14px;">
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($options['wrapper'] !== false): ?>
                    <div <?= $options['wrapperAttrs'] ?> >
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($showLabel && $options['label'] !== false): ?>
                    <?php if ($options['is_child']): ?>
                        <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
                    <?php else: ?>
                        <?= Form::label($name, $options['label'], $options['label_attr']) ?>
                        <div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($showField): ?>
                        <?php foreach ($options['choices'] as $key => $choice): ?>   
                            <?php $options['attr']['id'] = $key; ?>
                            <?= Form::radio($name, $choice, $choice == $options['checked'] ? true : false, $options['attr']) ?>
                            <label for="<?= $key; ?>"></label>
                            <label class="text-normal cursor-pointer mr-rt-8" for=<?= $key; ?>><?= $choice; ?></label>    
                        <?php endforeach; ?>   
                    </div>
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
                <div class="row" style="padding-right:15px;padding-left:14px;">
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options['wrapper'] !== false): ?>
                <div <?= $options['wrapperAttrs'] ?> >
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($showLabel && $options['label'] !== false): ?>
                <?php if ($options['is_child']): ?>
                    <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
                <?php else: ?>
                    <?= Form::label($name, $options['label'], $options['label_attr']) ?>
                    <div>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($showField): ?>
                    <?php foreach ($options['choices'] as $key => $choice): ?>   
                        <?php $options['attr']['id'] = $key; ?>
                        <?= Form::radio($name, $choice, $choice == $options['checked'] ? true : false, $options['attr']) ?>
                        <label for="<?= $key; ?>"></label>
                        <label class="text-normal cursor-pointer mr-rt-8" for=<?= $key; ?>><?= $choice; ?></label>    
                    <?php endforeach; ?>   
                </div>
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