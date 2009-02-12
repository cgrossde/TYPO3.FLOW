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
class Request {

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
	 * Pattern after which the controller object name is built
	 *
	 * @var string
	 */
	protected $controllerObjectNamePattern = 'F3\@package\Controller\@controllerController';

	/**
	 * Pattern after which the view object name is built
	 *
	 * @var string
	 */
	protected $viewObjectNamePattern = 'F3\@package\View\@controller@action@format';

	/**
	 * Package key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerPackageKey = 'FLOW3\MVC';

	/**
	 * Subpackage key of the controller which is supposed to handle this request.
	 *
	 * @var string
	 */
	protected $controllerSubpackageKey;

	/**
	 * @var string Object name of the controller which is supposed to handle this request.
	 */
	protected $controllerName = 'Default';

	/**
	 * @var string Name of the action the controller is supposed to take.
	 */
	protected $controllerActionName = 'index';

	/**
	 * @var ArrayObject The arguments for this request
	 */
	protected $arguments;

	/**
	 * @var string The requested representation format
	 */
	protected $format = 'txt';

	/**
	 * @var boolean If this request has been changed and needs to be dispatched again
	 */
	protected $dispatched = FALSE;

	/**
	 * Constructs this request
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {
		$this->arguments = new \ArrayObject;
	}

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
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
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
	 * @return boolean TRUE if this request has been disptached sucessfully
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Returns the object name of the controller defined by the package key and
	 * controller name
	 *
	 * @return string The controller's Object Name
	 * @throws \F3\FLOW3\MVC:Exception\NoSuchController if the controller does not exist
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectName() {
		$lowercaseObjectName = str_replace('@package', $this->controllerPackageKey, $this->controllerObjectNamePattern);
		$lowercaseObjectName = str_replace('@subpackage', $this->controllerSubpackageKey, $lowercaseObjectName);
		$lowercaseObjectName = strtolower(str_replace('@controller', $this->controllerName, $lowercaseObjectName));
		$objectName = $this->objectManager->getCaseSensitiveObjectName($lowercaseObjectName);
		if ($objectName === FALSE) throw new \F3\FLOW3\MVC\Exception\NoSuchController('The controller object "' . $lowercaseObjectName . '" does not exist.', 1220884009);

		return $objectName;
	}

	/**
	 * Sets the pattern for building the controller object name.
	 *
	 * The pattern may contain the placeholders "@package" and "@controller" which will be substituted
	 * by the real package key and controller name.
	 *
	 * @param string $pattern The pattern
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setControllerObjectNamePattern($pattern) {
		$this->controllerObjectNamePattern = $pattern;
	}

	/**
	 * Returns the pattern for building the controller object name.
	 *
	 * @return string $pattern The pattern
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getControllerObjectNamePattern() {
		return $this->controllerObjectNamePattern;
	}

	/**
	 * Sets the pattern for building the view object name
	 *
	 * @param string $pattern The view object name pattern, eg. \F3\@package\View::@controller@action
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setViewObjectNamePattern($pattern) {
		if (!is_string($pattern)) throw new \InvalidArgumentException('The view object name pattern must be a valid string, ' . gettype($pattern) . ' given.', 1221563219);
		$this->viewObjectNamePattern = $pattern;
	}

	/**
	 * Returns the View Object Name Pattern
	 *
	 * @return string The pattern
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getViewObjectNamePattern() {
		return $this->viewObjectNamePattern;
	}

	/**
	 * Returns the view's (possible) object name according to the defined view object
	 * name pattern and the specified values for package, controller, action and format.
	 *
	 * If no valid view object name could be resolved, FALSE is returned
	 *
	 * @return mixed Either the view object name or FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getViewObjectName() {
		$possibleViewName = $this->viewObjectNamePattern;
		$possibleViewName = str_replace('@package', $this->controllerPackageKey, $possibleViewName);
		$possibleViewName = str_replace('@subpackage', $this->controllerSubpackageKey, $possibleViewName);
		$possibleViewName = str_replace('@controller', $this->controllerName, $possibleViewName);
		$possibleViewName = str_replace('@action', $this->controllerActionName, $possibleViewName);

		$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(str_replace('@format', $this->format, $possibleViewName));
		if ($viewObjectName === FALSE) {
			$viewObjectName = $this->objectManager->getCaseSensitiveObjectName(str_replace('@format', '', $possibleViewName));
		}
		return $viewObjectName;
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
		if ($actionName{0} !== \F3\PHP6\Functions::strtolower($actionName{0})) throw new \F3\FLOW3\MVC\Exception\InvalidActionName('The action name must start with a lower case letter, "' . $actionName . '" does not match this criteria.', 1218473352);
		$this->controllerActionName = $actionName;
	}

	/**
	 * Returns the name of the action the controller is supposed to execute.
	 *
	 * @return string Action name
	 * @author Robert Lemke <robert@typo3.org>
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
		if (!is_string($argumentName) || \F3\PHP6\Functions::strlen($argumentName) == 0) throw new \F3\FLOW3\MVC\Exception\InvalidArgumentName('Invalid argument name.', 1210858767);
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param \ArrayObject $arguments An ArrayObject of argument names and their values
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setArguments(\ArrayObject $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @author Robert Lemke <robert@typo3.org>
	 * @throws \F3\FLOW3\MVC\Exception\NoSuchArgument if such an argument does not exist
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
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return \ArrayObject ArrayObject of arguments and their values (which may be arguments and values as well)
	 * @author Robert Lemke <robert@typo3.org>
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
	 */
	public function getFormat() {
		return $this->format;
	}
}
?>