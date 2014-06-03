<?php /* List file names & line numbers for all stack frames;
         clicking these links/buttons will display the code view
         for that particular frame */ ?>
<?php foreach($frames as $i => $frame): ?>
  <div class="frame <?php echo ($i == 0 ? 'active' : '') ?>" id="frame-line-<?php echo $i ?>">
      <div class="frame-method-info">
        <span class="frame-index"><?php echo (count($frames) - $i - 1) ?>.</span>
        <span class="frame-class"><?php echo $tpl->escape($frame->getClass() ?: '') ?></span>
        <span class="frame-function"><?php echo $tpl->escape($frame->getFunction() ?: '') ?></span>
      </div>

    <span class="frame-file">
      <?php echo ($frame->getFile(true) ?: '<#unknown>') ?><!--
   --><span class="frame-line"><?php echo (int) $frame->getLine() ?></span>
    </span>
  </div>
<?php endforeach ?>
