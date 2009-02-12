<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Configuration;

/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * @package FLOW3
 * @subpackage Configuration
 */

/**
 * A general purpose configuration container.
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Container implements \Countable, \Iterator, \ArrayAccess {

	/**
	 * @var array Configuration options and their values
	 */
	protected $options = array();

	/**
	 * @var boolean Whether this container is locked against write access or open
	 */
	protected $locked = FALSE;

	/**
	 * @var integer The current Iterator index
	 */
	protected $iteratorIndex = 0;

	/**
	 * @var integer The current number of options
	 */
	protected $iteratorCount = 0;

	/**
	 * Constructs the configuration container
	 *
	 * @param array $fromArray If specified, the configuration container will be intially built from the given array structure and values
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($fromArray = NULL) {
		if (is_array($fromArray)) {
			$this->setFromArray($fromArray);
		}
	}

	/**
	 * Sets the content of this configuration container by parsing the given array.
	 *
	 * @param array $fromArray Array structure (and values) which are supposed to be converted into container properties and sub containers
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFromArray(array $fromArray) {
		foreach ($fromArray as $key => $value) {
			if (is_array($value)) {
				$subContainer = new self($value);
				$this->offsetSet($key, $subContainer);
			} else {
				$this->offsetSet($key, $value);
			}
		}
	}

	/**
	 * Returns this configuration container (and possible sub containers) as an array
	 *
	 * @return array This container converted to an array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getAsArray() {
		$optionsArray = array();
		foreach ($this->options as $key => $value) {
			$optionsArray[$key] = ($value instanceof \F3\FLOW3\Configuration\Container) ? $value->getAsArray() : $value;
		}
		return $optionsArray;
	}

	/**
	 * Locks this configuration container agains write access.
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lock() {
		$this->locked = TRUE;
		foreach ($this->options as $option) {
			if ($option instanceof \F3\FLOW3\Configuration\Container) {
				$option->lock();
			}
		}
	}

	/**
	 * If this container is locked against write access.
	 *
	 * @return boolean TRUE if the container is locked
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isLocked() {
		return $this->locked;
	}

	/**
	 * Merges this container with another configuration container
	 *
	 * @param \F3\FLOW3\Configuration\Container $otherConfiguration The other configuration container
	 * @return \F3\FLOW3\Configuration\Container This container
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeWith(\F3\FLOW3\Configuration\Container $otherConfiguration) {
		foreach ($otherConfiguration as $optionName => $newOptionValue) {
			if ($newOptionValue instanceof \F3\FLOW3\Configuration\Container && array_key_exists($optionName, $this->options)) {
				$existingOptionValue = $this->__get($optionName);
				if ($existingOptionValue instanceof \F3\FLOW3\Configuration\Container) {
					$newOptionValue = $existingOptionValue->mergeWith($newOptionValue);
				}
			}
			$this->__set($optionName, $newOptionValue);
		}
		return $this;
	}

	/**
	 * Returns the number of configuration options
	 *
	 * @return integer Option count
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function count() {
		return $this->iteratorCount;
	}

	/**
	 * Returns the current configuration option
	 *
	 * @return mixed The current option's value
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function current() {
		return current($this->options);
	}

	/**
	 * Returns the key of the current configuration option
	 *
	 * @return string The current configuration option's key
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function key() {
		return key($this->options);
	}

	/**
	 * Returns the next configuration option
	 *
	 * @return mixed Value of the next configuration option
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function next() {
		$this->iteratorIndex ++;
		return next($this->options);
	}

	/**
	 * Rewinds the iterator index
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function rewind() {
		$this->iteratorIndex = 0;
		reset ($this->options);
	}

	/**
	 * Checks if the current index is valid
	 *
	 * @return boolean If the current index is valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function valid() {
		return $this->iteratorIndex < $this->iteratorCount;
	}

	/**
	 * Offset check for the ArrayAccess interface
	 *
	 * @param mixed $optionName
	 * @return boolean TRUE if the offset exists otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetExists($optionName) {
		return array_key_exists($optionName, $this->options);
	}

	/**
	 * Getter for the ArrayAccess interface
	 *
	 * @param mixed $optionName Name of the option to retrieve
	 * @return mixed The value
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetGet($optionName) {
		return $this->__get($optionName);
	}

	/**
	 * Setter for the ArrayAccess interface
	 *
	 * @param mixed $optionName Name of the option to set
	 * @param mixed $optionValue New value for the option
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetSet($optionName, $optionValue) {
		$this->__set($optionName, $optionValue);
	}

	/**
	 * Unsetter for the ArrayAccess interface
	 *
	 * @param mixed $optionName Name of the option to unset
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function offsetUnset($optionName) {
		$this->__unset($optionName);
	}

	/**
	 * Magic getter method for configuration options. If an option does not exist,
	 * it will be created automatically - if this container is not locked.
	 *
	 * @param string $optionName Name of the configuration option to retrieve
	 * @return mixed The option value
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __get($optionName) {
		if (!array_key_exists($optionName, $this->options)) {
			if ($this->locked) throw new \F3\FLOW3\Configuration\Exception\NoSuchOption('An option "' . $optionName . '" does not exist in this configuration container.', 1216385011);
			$this->__set($optionName, new self());
		}
		return $this->options[$optionName];
	}

	/**
	 * Magic setter method for configuration options.
	 *
	 * @param string $optionName Name of the configuration option to set
	 * @param mixed $optionValue The option value
	 * @return void
	 * @throws \F3\FLOW3\Configuration\Exception\ContainerIsLocked if the container is locked
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __set($optionName, $optionValue) {
		if ($this->locked && !array_key_exists($optionName, $this->options)) throw new \F3\FLOW3\Configuration\Exception\ContainerIsLocked('You tried to create a new configuration option "' . $optionName . '" but the configuration container is already locked. Maybe a spelling mistake?', 1206023011);
		$this->options[$optionName] = $optionValue;
		$this->iteratorCount = count($this->options);
	}

	/**
	 * Magic isset method for configuration options.
	 *
	 * @param string $optionName Name of the configuration option to check
	 * @return boolean TRUE if the option is set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __isset($optionName) {
		return array_key_exists($optionName, $this->options);
	}

	/**
	 * Magic unsetter method for configuration options.
	 *
	 * @param string $optionName Name of the configuration option to unset
	 * @return void
	 * @throws \F3\FLOW3\Configuration\Exception\ContainerIsLocked if the container is locked
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __unset($optionName) {
		if ($this->locked) throw new \F3\FLOW3\Configuration\Exception\ContainerIsLocked('You tried to unset the configuration option "' . $optionName . '" but the configuration container is locked.', 1206023012);
		unset($this->options[$optionName]);
		$this->iteratorCount = count($this->options);
	}

	/**
	 * Magic method to allow setting of configuration options via dummy setters in the format "set[OptionName]([optionValue])".
	 *
	 * @param string $methodName Name of the called setter method.
	 * @param array $arguments Method arguments, passed to the configuration option.
	 * @return \F3\FLOW3\Configuration\Container This configuration container object
	 * @throws \F3\FLOW3\Configuration\Exception if $methodName does not start with "set" or number of arguments are empty
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __call($methodName, $arguments) {
		if (substr($methodName, 0, 3) != 'set') {
			throw new \F3\FLOW3\Configuration\Exception('Method "' . $methodName . '" does not exist.', 1213444319);
		}
		if (count($arguments) != 1) {
			throw new \F3\FLOW3\Configuration\Exception('You have to pass exactly one argument to a configuration option setter.', 1213444809);
		}
		$optionName = lcfirst(substr($methodName, 3));
		$this->__set($optionName, $arguments[0]);

		return $this;
	}
}
?>