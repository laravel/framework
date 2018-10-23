<?php
    $attributes = "";
    foreach ($options['attr'] as $key => $value) {
        $attributes .= $key . "=\"" . $value ."\"";
    }
?>
<?php if ($showField): ?>
    <tr>
        <td colspan="2" class="FieldTitleView"><h4 <?= $attributes ?>><?= $options["label"] ?></h4></td>
    </tr>
<?php endif; ?>