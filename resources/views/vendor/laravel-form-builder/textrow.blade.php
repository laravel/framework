<?php
    $attributes = "";
    foreach ($options['attr'] as $key => $value) {
        $attributes .= $key . "=\"" . $value ."\"";
    }
?>
<?php if ($showField): ?>
    <tr>
        <td class="TextRowView"><span style="font-weight:bold"><?= $options["label"] ?></span></td>
        <td><?= $options["content"] ?></td>
    </tr>
<?php endif; ?>