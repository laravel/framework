<?php if ($options['help_block']['text'] && !$options['is_child']): ?>
    <<?= $options['help_block']['tag'] ?> <?= $options['help_block']['helpBlockAttrs'] ?>>
        <?= $options['help_block']['text'] ?>
    </<?= $options['help_block']['tag'] ?>>
<?php endif; ?>
