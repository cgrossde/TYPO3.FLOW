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
 * @version $Id$
 */

/**
 * A general purpose configuration manager
 *
 * @package FLOW3
 * @subpackage Configuration
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Manager {

	const CONFIGURATION_TYPE_FLOW3 = 'FLOW3';
	const CONFIGURATION_TYPE_PACKAGES = 'Packages';
	const CONFIGURATION_TYPE_OBJECTS = 'Objects';
	const CONFIGURATION_TYPE_SETTINGS = 'Settings';
	const CONFIGURATION_TYPE_ROUTES = 'Routes';
	const CONFIGURATION_TYPE_SIGNALSSLOTS = 'SignalsSlots';
	const CONFIGURATION_TYPE_CACHES = 'Caches';

	/**
	 * @var \F3\FLOW3\Package\ManagerInterface
	 */
	protected $packageManager;

	/**
	 * @var string The application context of the configuration to manage
	 */
	protected $context;

	/**
	 * Storage for the settings, loaded by loadGlobalSettings()
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Storage of the raw special configurations
	 *
	 * @var array
	 */
	protected $configurations = array(
		'Routes' => array(),
		'SignalsSlots' => array(),
		'Caches' => array()
	);

	/**
	 * The configuration sources used for loading the raw configuration
	 *
	 * @var array
	 */
	protected $configurationSources;

	/**
	 * Constructs the configuration manager
	 *
	 * @param string $context The application context to fetch configuration for.
	 * @param array $configurationSources An array of configuration sources
	 */
	public function __construct($context, array $configurationSources) {
		$this->context = $context;
		$this->configurationSources = $configurationSources;
	}

	/**
	 * Injects the package manager
	 *
	 * @param \F3\FLOW3\Package\ManagerInterface $packageManager
	 * @return void
	 */
	public function injectPackageManager(\F3\FLOW3\Package\ManagerInterface $packageManager) {
		$this->packageManager = $packageManager;
	}

	/**
	 * Returns an array with the settings defined for the specified package.
	 *
	 * @param string $packageKey Key of the package to return the settings for
	 * @return array The settings of the specified package
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSettings($packageKey) {
		if (isset($this->settings[$packageKey])) {
			$settings = $this->settings[$packageKey];
		} else {
			$settings = array();
		}
		return $settings;
	}

	/**
	 * Loads the FLOW3 core settings defined in the FLOW3 package and the global
	 * configuration directories.
	 *
	 * The FLOW3 settings can be retrieved like any other setting through the
	 * getSettings() method but need to be loaded separately because they are
	 * needed way earlier in the bootstrap than the package's settings.
	 *
	 * @return void
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadFLOW3Settings() {
		$settings = array();
		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_FLOW3 . 'Configuration/FLOW3'));
		}

		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'FLOW3', TRUE));
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/FLOW3', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings['FLOW3'] = $settings;
	}

	/**
	 * Loads the settings defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's settings registry
	 * and can be retrieved with the getSettings() method.
	 *
	 * @param array $packages An array of Package object
	 * @return void
	 * @see getSettings()
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadGlobalSettings(array $packages) {
		$settings = array();
		if (isset($packages['FLOW3'])) unset ($packages['FLOW3']);

		foreach ($packages as $package) {
			foreach ($this->configurationSources as $configurationSource) {
				$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load($package->getConfigurationPath() . 'Settings'));
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . 'Settings', TRUE));
			$settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($settings, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/Settings', TRUE));
		}
		$this->postProcessSettings($settings);
		$this->settings = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->settings, $settings);
	}

	/**
	 * Loads special configuration defined in the specified packages and merges them with
	 * those potentially existing in the global configuration folders.
	 *
	 * The result is stored in the configuration manager's configuration registry
	 * and can be retrieved with the getSpecialConfiguration() method. However note
	 * that this is only the raw information which will be further processed by other
	 * parts of FLOW3
	 *
	 * @param string $configurationType The kind of configuration to load - must be one of the CONFIGURATION_TYPE_* constants
	 * @param array $packages An array of Package objects to consider
	 * @return void
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function loadSpecialConfiguration($configurationType, array $packages) {
		foreach ($packages as $packageKey => $package) {
			foreach ($this->configurationSources as $configurationSource) {
				$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load($package->getConfigurationPath() . $configurationType));
			}
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$this->configurations[$configurationType] = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($this->configurations[$configurationType], $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}
		$this->postProcessSettings($this->configurations[$configurationType]);
	}

	/**
	 * Loads and returns the specified raw configuration. The actual configuration will be
	 * merged from different sources in a defined order.
	 *
	 * Note that this is a very low level method and usually only makes sense to be used
	 * by FLOW3 internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param \F3\FLOW3\Package\Package $package The package to return the configuration for
	 * @return array The configuration
	 * @throws \F3\FLOW3\Configuration\Exception\InvalidConfigurationType on invalid configuration types
	 * @internal
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getSpecialConfiguration($configurationType, $package = NULL) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_ROUTES :
			case self::CONFIGURATION_TYPE_SIGNALSSLOTS :
			case self::CONFIGURATION_TYPE_CACHES :
				return $this->configurations[$configurationType];
			case self::CONFIGURATION_TYPE_PACKAGES :
			case self::CONFIGURATION_TYPE_OBJECTS :
				if (!is_object($package)) throw new \InvalidArgumentException('No package specified.', 1233336279);
			break;
			default:
				throw new \F3\FLOW3\Configuration\Exception\InvalidConfigurationType('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}
		$configuration = array();
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load($package->getConfigurationPath() . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $configurationType));
		}
		foreach ($this->configurationSources as $configurationSource) {
			$configuration = \F3\FLOW3\Utility\Arrays::arrayMergeRecursiveOverrule($configuration, $configurationSource->load(FLOW3_PATH_CONFIGURATION . $this->context . '/' . $configurationType));
		}

		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_PACKAGES :
				return (isset($configuration[$package->getPackageKey()])) ? $configuration[$package->getPackageKey()] : array();
			case self::CONFIGURATION_TYPE_OBJECTS :
				return $configuration;
		}
	}

	/**
	 * Post processes the given settings array by replacing constants with their
	 * actual value.
	 *
	 * This is a preliminary solution, we'll surely have some better way to handle
	 * this soon.
	 *
	 * @param array &$settings The settings to post process. The results are stored directly in the given array
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	protected function postProcessSettings(&$settings) {
		foreach ($settings as $key => $setting) {
			if (is_array($setting)) {
				$this->postProcessSettings($settings[$key]);
			} elseif (is_string($setting)) {
				$matches = array();
				preg_match_all('/(?:%)([a-zA-Z_0-9]+)(?:%)/', $setting, $matches);
				if (count($matches[1]) > 0) {
					foreach ($matches[1] as $match) {
						if (defined($match)) $settings[$key] = str_replace('%' . $match . '%', constant($match), $settings[$key]);
					}
				}
			}
		}
	}
}
?>