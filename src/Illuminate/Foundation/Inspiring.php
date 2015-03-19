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

			'When there is no desire, all things are at peace. - Laozi',
			'Simplicity is the ultimate sophistication. - Leonardo da Vinci',
			'Simplicity is the essence of happiness. - Cedric Bledsoe',
			'Smile, breathe, and go slowly. - Thich Nhat Hanh',
			'Simplicity is an acquired taste. - Katharine Gerould',
			'There are two ways to be rich: One is by acquiring much, and the other is by desiring little. - Jackie French Koller',

		])->random();
	}

}
