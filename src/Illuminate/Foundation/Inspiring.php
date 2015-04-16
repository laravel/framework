<?php namespace Illuminate\Foundation;

use Illuminate\Support\Collection;

class Inspiring {

	/**
	 * Get an inspiring quote.
	 *
	 * Taylor & Dayle made this commit from Jungfraujoch. (11,333 ft.)
	 *
	 * @return string
	 */
	public static function quote()
	{
		return Collection::make([
			'Simplicity is the ultimate sophistication. - Leonardo da Vinci',
			'When there is no desire, all things are at peace. - Laozi',
			'Simplicity is the essence of happiness. - Cedric Bledsoe',
			'Simplicity is an acquired taste. - Katharine Gerould',
			'Smile, breathe, and go slowly. - Thich Nhat Hanh',
			'Well begun is half done. - Aristotle',

		])->random();
	}

}
