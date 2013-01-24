<?php
	$presenter = new Illuminate\Pagination\BootstrapPresenter($paginator);
?>

<?php if ($paginator->getLastPage() > 1): ?>
	<div class="pagination<?php $presenter->getClasses(); ?>">
		<ul>
			<?php echo $presenter->render(); ?>
		</ul>
	</div>
<?php endif; ?>