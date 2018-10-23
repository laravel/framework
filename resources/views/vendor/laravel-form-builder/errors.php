<?php if ($showError && isset($errors)): ?>
    <?php foreach ($errors->get($nameKey) as $err): ?>
        <div <?= $options['errorAttrs'] ?>><?= $err ?></div>
    <?php endforeach; ?>
<?php endif; ?>

