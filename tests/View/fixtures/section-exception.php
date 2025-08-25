<?php echo $__env->make('layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->startSection('content'); ?>
<?php throw new Exception('section exception message') ?>
<?php $__env->stopSection(); ?>
