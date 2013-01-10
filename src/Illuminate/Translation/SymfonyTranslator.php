<?php namespace Illuminate\Translation;

class SymfonyTranslator extends \Symfony\Component\Translation\Translator {

	/**
	 * Refresh the catalogue for a given locale.
	 *
	 * @param  string  $locale
	 * @return void
	 */
	public function refreshCatalogue($locale)
	{
		$this->loadCatalogue($locale);
	}

}