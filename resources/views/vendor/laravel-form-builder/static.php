<?php if (isset($options["onlyToManager"])): ?>
    <?php if (($options["onlyToManager"] === true) && !(Auth::user()->Role->pluck("Slug")->contains(env("CUSTOMER_ROLE_SLUG")))): ?>
        <?php if ($showLabel && $showField): ?>
            <?php if (isset($options["row_start"])): ?>
                <?php if ($options["row_start"] === true): ?>
                    <div class="row pd-rt-15 pd-lt-14">
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($options['wrapper'] !== false): ?>
                    <div <?= $options['wrapperAttrs'] ?> >
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($showLabel && $options['label'] !== false): ?>
                    <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
                <?php endif; ?>
                <?php if ($showField): ?>
                    <?php if (isset($options['IsSiteUrl'])): ?>
                        <?php if ($options['IsSiteUrl'] === true): ?>
                            <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= "<a href='http://hechpe.com/' id='CompanyURL'>www.hechpe.com</a>" ?></<?= $options['tag'] ?>>
                        <?php else: ?>
                            <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= $options['value'] ?></<?= $options['tag'] ?>>
                        <?php endif; ?> 
                    <?php endif; ?>
                    <?php if (isset($options['IsCheckedBy'])): ?>
                        <?php if ($options['IsCheckedBy'] === true): ?>
                            <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= Auth::user()->Person->FirstName . " " . Auth::user()->Person->LastName; ?></<?= $options['tag'] ?>>
                        <?php else: ?>
                            <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= $options['value'] ?></<?= $options['tag'] ?>>
                        <?php endif; ?> 
                    <?php endif; ?>
                    <?php include 'help_block.php' ?>
                <?php endif; ?>
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
                <div class="row pd-rt-15 pd-lt-14">
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($options['wrapper'] !== false): ?>
                <div <?= $options['wrapperAttrs'] ?> >
                <?php endif; ?>
            <?php endif; ?>
            <?php if ($showLabel && $options['label'] !== false): ?>
                <label <?= $options['labelAttrs'] ?>><?= $options['label'] ?></label>
            <?php endif; ?>
            <?php if ($showField): ?>
                <?php if (isset($options['IsSiteUrl'])): ?>
                    <?php if ($options['IsSiteUrl'] === true): ?>
                        <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= "<a href='http://hechpe.com/' id='CompanyURL'>www.hechpe.com</a>" ?></<?= $options['tag'] ?>>
                    <?php else: ?>
                        <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= $options['value'] ?></<?= $options['tag'] ?>>
                    <?php endif; ?> 
                <?php endif; ?>
                <?php if (isset($options['IsCheckedBy'])): ?>
                    <?php if ($options['IsCheckedBy'] === true): ?>
                        <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= Auth::user()->Person->FirstName . " " . Auth::user()->Person->LastName; ?></<?= $options['tag'] ?>>
                    <?php else: ?>
                        <<?= $options['tag'] ?> <?= $options['elemAttrs'] ?>><?= $options['value'] ?></<?= $options['tag'] ?>>
                    <?php endif; ?> 
                <?php endif; ?>
                <?php include 'help_block.php' ?>
            <?php endif; ?>
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
