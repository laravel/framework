<?php
    $attributes = "";
    foreach ($options['attr'] as $key => $value) {
        $attributes .= $key . "=\"" . $value ."\"";
    }
?>
@if(isset($options["onlyToManager"]))
    @if(($options["onlyToManager"] === true) && (Auth::user()->Role->pluck("Slug")->contains(env("MANAGER_ROLE_SLUG"))))
        <?php if ($showField): ?>
    <h5 <?= $attributes ?>><?= $options["label"] ?></h5>
        <?php endif; ?>
    @endif
@elseif(isset($options["sub_label"]) && $options['sub_label'] ===true)
<label <?= $attributes ?>><?= $options["label"] ?></label>
@else
    <?php if ($showField): ?>
        <h5 <?= $attributes ?>><?= $options["label"] ?></h5>
    <?php endif; ?>
@endif