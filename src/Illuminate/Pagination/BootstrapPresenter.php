<?php namespace Illuminate\Pagination;

class BootstrapPresenter extends Presenter {

	public function getActivePageWrapper($text) 
	{
		return '<li class="active"><span>' . $text . '</span></li>';
	}

	public function getDisabledTextWrapper($text) 
	{
		return '<li class="disabled"><span>' . $text . '</span></li>';
	}

	public function getPageLinkWrapper($url, $page) 
	{
		return '<li><a href="' . $url . '">' . $page . '</a></li>';
	}

}
