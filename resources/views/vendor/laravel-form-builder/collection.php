<?php if ($showLabel && $showField): ?>
    <?php if ($options['wrapper'] !== false): ?>
    <div <?= $options['wrapperAttrs'] ?> >
    <?php endif; ?>
<?php endif; ?>

<?php if ($showLabel && $options['label'] !== false): ?>
    <?= Form::label($name, $options['label'], $options['label_attr']) ?>
<?php endif; ?>

<?php if ($showField): ?>
    <?php foreach ((array)$options['children'] as $child): ?>
        <?= $child->render() ?>
    <?php endforeach; ?>

    <?php include 'help_block.php' ?>

<?php endif; ?>

<?php include 'errors.php' ?>

<?php if ($showLabel && $showField): ?>
    <?php if ($options['wrapper'] !== false): ?>
    </div>
    <?php endif; ?>
<?php endif; ?>
