<?php
	$presenter = new Illuminate\Pagination\BootstrapPresenter($paginator);

	$trans = $environment->getTranslator();
?>

<?php if ($paginator->getLastPage() > 1): ?>
	<div class="pagination">
		<ul class="pager">
			<?php
				echo $presenter->getPrevious($trans->trans('pagination.previous'));

				echo $presenter->getNext($trans->trans('pagination.next'));
			?>
		</ul>
	</div>
<?php endif; ?>