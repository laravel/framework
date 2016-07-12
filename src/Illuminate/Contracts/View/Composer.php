<?php namespace Illuminate\Contracts\View;

use Illuminate\Contracts\View\View;

interface Composer {

	/**
	 * Bind data to the view.
	 *
	 * @param  \Illuminate\Contracts\View\View  $view
	 * @return void
	 */
	public function compose(View $view);

}
