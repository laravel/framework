<?php if (isset($options["onlyToManager"])): ?>
    <?php if(($options["onlyToManager"] === true) && (Auth::user()->Role->pluck("Slug")->contains(env("MANAGER_ROLE_SLUG")))): ?>
        <?php if (isset($options["row_start"])): ?>
            <?php if ($options["row_start"] === true): ?>
                <div class="row EnquiryBottomRow">
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($options['wrapper'] !== false): ?>
        <div <?= $options['wrapperAttrs'] ?> >
        <?php endif; ?>

        <?= Form::button($options['label'], $options['attr']) ?>
        <?php include 'help_block.php' ?>

        <?php if ($options['wrapper'] !== false): ?>
        </div>
        <?php endif; ?>
        <?php if (isset($options["row_end"])): ?>
            <?php if ($options["row_end"] === true): ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <?php if (isset($options["row_start"])): ?>
        <?php if ($options["row_start"] === true): ?>
            <div class="row EnquiryBottomRow">
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($options['wrapper'] !== false): ?>
    <div <?= $options['wrapperAttrs'] ?> >
    <?php endif; ?>

    <?= Form::button($options['label'], $options['attr']) ?>
    <?php include 'help_block.php' ?>

    <?php if ($options['wrapper'] !== false): ?>
    </div>
    <?php endif; ?>
    <?php if (isset($options["row_end"])): ?>
        <?php if ($options["row_end"] === true): ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>
