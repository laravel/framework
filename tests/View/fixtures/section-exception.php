<?php echo $__env->make('layout', array_except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->startSection('content'); ?>
<?php throw new Exception('section exception message') ?>
<?php $__env->stopSection(); ?>
