<?php namespace Illuminate\Pagination;

use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * DEPRECATED: Please use Illuminate\Pagination\Factory instead!
 */
class Environment extends Factory {

	/**
	 * Create a new pagination factory.
	 *
	 * @param  \Symfony\Component\HttpFoundation\Request  $request
	 * @param  \Illuminate\View\Factory  $view
	 * @param  \Symfony\Component\Translation\TranslatorInterface  $trans
	 * @param  string  $pageName
	 * @return void
	 */
	public function __construct(Request $request, ViewFactory $view, TranslatorInterface $trans, $pageName = 'page')
	{
		parent::__construct($request, $view, $trans, $pageName);
	}

}