<?php namespace Illuminate\Contracts\Pagination;

interface Paginator {

	public function urls();
	public function items();
	public function firstItem();
	public function lastItem();
	public function perPage();
	public function currentPage();
	public function lastPage();
	public function render(Presenter $presenter = null);

}
