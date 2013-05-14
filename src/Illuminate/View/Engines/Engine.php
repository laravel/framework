<?php namespace Illuminate\View\Engines;

abstract class Engine {

	/**
	 * The view that was last to be rendered.
	 *
	 * @var string
	 */
	protected $lastRendered;

	/**
	 * Determine if the engine is sectionable.
	 *
	 * @return bool
	 */
	public function isSectionable()
	{
		return $this instanceof SectionableInterface;
	}

	/**
	 * Get the last view that was rendered.
	 *
	 * @return string
	 */
	public function getLastRendered()
	{
		return $this->lastRendered;
	}

}