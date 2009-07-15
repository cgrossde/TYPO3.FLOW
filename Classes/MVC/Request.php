<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\MVC;

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
 * @subpackage MVC
 * @version $Id$
 */

/**
 * Represents a generic request.
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Request implements \F3\FLOW3\MVC\RequestInterface {

	const PATTERN_MATCH_FORMAT = '/^[a-z0-9]{1,5}$/';

	/**
	 * @var \F3\FLOW3\Object\ManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'F3\@package\Controller\@controllerController';

	/**
	 * Package key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerPackageKey = NULL;

	/**
	 * Subpackage key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerSubpackageKey = NULL;

	/**
	 * @var string Object name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Standard';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var array The arguments for this request
	 */
	protected $arguments = array();

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * @var array Errors that occured during this request
	 */
	protected $errors = array();

	/**
	 * Injects the object manager
	 *
	 * @param \F3\FLOW3\Object\ManagerInterface $objectManager A reference to the object manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectObjectManager(\F3\FLOW3\Object\ManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Injects the package
	 *
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager A reference to the package manager
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectPackageManager(\F3\FLOW3\Package\ManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Injects the FLOW3 settings
	 *
	 * @param array $settings The FLOW3 settings
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 * @api
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached successfully
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchController if the controller does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getControllerObjectName() {
		$packageKey = $this->controllerPackageKey;
		if ($this->controllerSubpackageKey !== NULL && $this->controllerSubpackageKey !== '') {
			$packageKey.= '\\' . $this->controllerSubpackageKey;
		}
		$possibleObjectName = $this->controllerObjectNamePattern;
		$possibleObjectName = str_replace('@package', $packageKey, $possibleObjectName);
		$possibleObjectName = str_replace('@controller', $this->controllerName, $possibleObjectName);
		$lowercaseObjectName = strtolower($possibleObjectName);

		$objectName = $this->objectManager->getCaseSensitiveObjectName($lowercaseObjectName);
		if ($objectName === FALSE) $objectName = $this->settings['mvc']['notFoundController'];

		return $objectName;
	}

	/**
	 * Sets the package key of the controller.
	 *
	 * @param string $packageKey The package key.
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidPackageKey if the package key is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerPackageKey($packageKey) {
		$upperCamelCasedPackageKey = $this->packageManager->getCaseSensitivePackageKey($packageKey);
		if ($upperCamelCasedPackageKey === FALSE) throw new \F3\FLOW3\MVC\Exception\InvalidPackageKey('"' . $packageKey . '" is not a valid package key.', 1217961104);
		$this->controllerPackageKey = $upperCamelCasedPackageKey;
	}

	/**
	 * Returns the package key of the specified controller.
	 *
	 * @return string The package key
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerPackageKey() {
		return $this->controllerPackageKey;
	}

	/**
	 * Sets the subpackage key of the controller.
	 *
	 * @param string $subpackageKey The subpackage key.
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function setControllerSubpackageKey($subpackageKey) {
		$this->controllerSubpackageKey = $subpackageKey;
	}

	/**
	 * Returns the subpackage key of the specified controller.
	 *
	 * @return string The subpackage key
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @api
	 */
	public function getControllerSubpackageKey() {
		return $this->controllerSubpackageKey;
	}

	/**
	 * Sets the name of the controller which is supposed to handle the request.
	 * Note: This is not the object name of the controller!
	 *
	 * @param string $controllerName Name of the controller
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerName($controllerName) {
		if (!is_string($controllerName)) throw new \F3\FLOW3\MVC\Exception\InvalidControllerName('The controller name must be a valid string, ' . gettype($controllerName) . ' given.', 1187176358);
		if (strpos($controllerName, '_') !== FALSE) throw new \F3\FLOW3\MVC\Exception\InvalidControllerName('The controller name must not contain underscores.', 1217846412);
		$this->controllerName = $controllerName;
	}

	/**
	 * Returns the object name of the controller supposed to handle this request, if one
	 * was set already (if not, the name of the default controller is returned)
	 *
	 * @return string Object name of the controller
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerName() {
		return $this->controllerName;
	}

	/**
	 * Sets the name of the action contained in this request.
	 *
	 * Note that the action name must start with a lower case letter.
	 *
	 * @param string $actionName: Name of the action to execute by the controller
	 * @return void
	 * @throws \F3\FLOW3\MVC\Exception\InvalidActionName if the action name is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerActionName($actionName) {
		if (!is_string($actionName)) throw new \F3\FLOW3\MVC\Exception\InvalidActionName('The action name must be a valid string, ' . gettype($actionName) . ' given (' . $actionName . ').', 1187176358);
		if ($actionName[0] !== \F3\PHP6\Functions::strtolower($actionName[0])) throw new \F3\FLOW3\MVC\Exception\InvalidActionName('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getControllerActionName() {
		return $this->controllerActionName;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || \F3\PHP6\Functions::strlen($argumentName) === 0) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentName('Invalid argument name.', 1210858767);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchArgument if such an argument does not exist
	 * @api
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) throw new \F3\FLOW3\MVC\Exception\NoSuchArgument('An argument "' . $argumentName . '" does not exist for this request.', 1176558158);
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Sets the requested representation format
	 *
	 * @param string $format The desired format, something like "html", "xml", "png", "json" or the like.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFormat($format) {
		if (!preg_match(self::PATTERN_MATCH_FORMAT, $format)) throw new \F3\FLOW3\MVC\Exception\InvalidFormat('An invalid request format (' . $format . ') was given.', 1218015038);
		$this->format = $format;
	}

	/**
	 * Returns the requested representation format
	 *
	 * @return string The desired format, something like "html", "xml", "png", "json" or the like.
	 * @author Robert Lemke <robert@typo3.org>
	 * @api
	 */
	public function getFormat() {
		return $this->format;
	}

	/**
	 * Set errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @param array $errors An array of \F3\FLOW3\Error\Error objects
	 * @return void
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function setErrors(array $errors) {
		$this->errors = $errors;
	}

	/**
	 * Get errors that occured during the request (e.g. argument mapping errors)
	 *
	 * @return array The errors that occured during the request
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function getErrors() {
		return $this->errors;
	}
}
?>
