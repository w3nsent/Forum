<?php

/**
 * ModernStatistic accessor class.
 *
 * @package Brivium_ModernStatistic
 */
class Brivium_ModernStatistic_ModernStatistic
{
	/**
	 * Collection of modernStatistics.
	 *
	 * @var array
	 */
	protected $_modernStatistics = array();

	/**
	 * Constructor. Sets up the accessor using the provided events.
	 *
	 * @param array $modernStatistics Collection of events. Keys represent event names.
	 */
	public function __construct(array $modernStatistics)
	{
		$this->setModernStatistics($modernStatistics);
	}

	/**
	 * Gets an event. If the event exists and is an array, then...
	 * 	* if no sub-event is specified but an $modernStatisticId key exists in the event, return the value for that key
	 *  * if no sub-event is specified and no $modernStatisticId key exists, return the whole event array
	 *  * if the sub-event === false, the entire event is returned, regardless of what keys exist
	 *  * if a sub-event is specified and the key exists, return the value for that key
	 *  * if a sub-event is specified and the key does not exist, return null
	 * If the event is not an array, then the value of the event is returned (provided no sub-event is specified).
	 * Otherwise, null is returned.
	 *
	 * @param string $modernStatisticId Name of the event
	 * @param null|false|string $subModernStatistic Sub-event. See above for usage.
	 *
	 * @return null|mixed Null if the event doesn't exist (see above) or the event's value.
	 */
	public function get($modernStatisticId)
	{
		if (!isset($this->_modernStatistics[$modernStatisticId]))
		{
			return null;
		}

		$modernStatistics = $this->_modernStatistics[$modernStatisticId];
		if (is_array($modernStatistics))
		{
			return $modernStatistics;
		}
		else
		{
			return null;
		}
	}
	
	/**
	 * Gets all events in their raw form.
	 *
	 * @return array
	 */
	public function getModernStatistics()
	{
		return $this->_modernStatistics;
	}

	/**
	 * Sets the collection of events manually.
	 *
	 * @param array $modernStatistics
	 */
	public function setModernStatistics(array $modernStatistics)
	{
		$this->_modernStatistics = $modernStatistics;
	}

	/**
	 * Magic getter for first-order events. This method cannot be used
	 * for getting a sub-event! You must use {@link get()} for that.
	 *
	 * This is equivalent to calling get() with no sub-event, which means
	 * the "main" sub-event will be returned (if applicable).
	 *
	 * @param string $modernStatistic
	 *
	 * @return null|mixed
	 */
	public function __get($modernStatistic)
	{
		return $this->get($modernStatistic);
	}

	/**
	 * Returns true if the named event exists. Do not use this approach
	 * for sub-events!
	 *
	 * This is equivalent to calling get() with no sub-event, which means
	 * the "main" sub-event will be returned (if applicable).
	 *
	 * @param string $modernStatistic
	 *
	 * @return boolean
	 */
	public function __isset($modernStatistic)
	{
		return ($this->get($modernStatistic) !== null);
	}

	/**
	 * Magic set method. Only sets whole events.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}
}
