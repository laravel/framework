<?php
    $attributes = "";
    foreach ($options['attr'] as $key => $value) {
        $attributes .= $key . "=\"" . $value ."\"";
    }
?>
@if(isset($options["onlyToManager"]))
    @if(($options["onlyToManager"] === true) && !(Auth::user()->Role->pluck("Slug")->contains(env("CUSTOMER_ROLE_SLUG"))))
        <?php if ($showField): ?>
            <h4 <?= $attributes ?>><?= $options["label"] ?></h4>
        <?php endif; ?>
    @endif
@else
    <?php if ($showField): ?>
        <h4 <?= $attributes ?>><?= $options["label"] ?>
            <?php if (isset($options['appendMoreText'])): ?>
                <?php if ($options['appendMoreText'] === true): ?> 
            <small style="font-size: 15px;"><?= $options['textToAppend']; ?></small>
                <?php endif; ?>
            <?php endif; ?></h4>
    <?php endif; ?>
@endif