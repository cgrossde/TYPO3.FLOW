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
 * @subpackage Tests
 * @version $Id$
 */

/**
 * Testcase for the configuration container class
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ContainerTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function simpleOptionCanBeAddedThroughSimpleAssignment() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->newOption = 'testValue';
		$this->assertEquals('testValue', $configuration->newOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFly() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->parentOption->childOption = 'the child';
		$this->assertEquals('the child', $configuration->parentOption->childOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyOnThirdLevel() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->parentOption->childOption->grandChildOption = 'the grand child';
		$this->assertEquals('the grand child', $configuration->parentOption->childOption->grandChildOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function optionValuesCanBeArrays() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->someOption = array(1, 2, 3);
		$configuration->firstLevel->anotherOption = array(4, 5, 6);
		$this->assertEquals(array(1, 2, 3), $configuration->someOption, 'The retrieved value was not as expected.');
		$this->assertEquals(array(4, 5, 6), $configuration->firstLevel->anotherOption, 'The retrieved value of the other option was not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function containerCanBeLocked() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->lock();
		$this->assertTrue($configuration->isLocked(), 'Container could not be locked.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function lockingTheContainerAlsoLocksAllSubContainers() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->subConfiguration->subSubConfiguration;
		$configuration->otherOption = array('x' => 'y');

		$configuration->lock();
		$this->assertTrue($configuration->subConfiguration->isLocked(), 'sub configuration is not locked');
		$this->assertTrue($configuration->subConfiguration->subSubConfiguration->isLocked(), 'sub sub configuration is not locked');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function gettingOptionsFromLockedContainerIsAllowed() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->someOption = 'some value';
		$configuration->lock();
		$this->assertEquals('some value', $configuration->someOption, 'Could not retrieve the option.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 * @expectedException \F3\FLOW3\Configuration\Exception\ContainerIsLocked
	 */
	public function introducingNewOptionsOnLockedContainerResultsInException() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->lock();

		$configuration->someNewOption = 'some value';
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function modifyingExistingOptionsOnLockedContainerIsAllowed() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->existingOption = 'old';
		$configuration->lock();
		$configuration->existingOption = 'new';

		$this->assertEquals('new', $configuration->existingOption);
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function foreachCanTraverseOverFirstLevelOptions() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->firstOption = '1';
		$configuration->secondOption = '2';
		$configuration->thirdOption = '3';

		$keys = '';
		$values = '';
		foreach ($configuration as $key => $value) {
			$keys .= $key;
			$values .= $value;
		}
		$this->assertEquals('firstOptionsecondOptionthirdOption', $keys, 'Keys did not match.');
		$this->assertEquals('123', $values, 'Values did not match.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function issetReturnsTheCorrectResult() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->someOption = 'some value';
		$this->assertTrue(isset($configuration->someOption), 'isset() did not return TRUE.');
		$this->assertFalse(isset($configuration->otherOption), 'isset() did not return FALSE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unsetReallyUnsetsOption() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->someOption = 'some value';
		unset($configuration->someOption);
		$this->assertFalse(isset($configuration->someOption), 'isset() returned TRUE.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeJustAddsNonConflictingOptionsToTheExistingContainer() {
		$configurationA = new \F3\FLOW3\Configuration\Container();
		$configurationA->firstOption = 'firstValue';
		$configurationB = new \F3\FLOW3\Configuration\Container();
		$configurationB->secondOption = 'secondValue';

		$expectedConfiguration = new \F3\FLOW3\Configuration\Container();
		$expectedConfiguration->firstOption = 'firstValue';
		$expectedConfiguration->secondOption = 'secondValue';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeAlsoMergesNonConflictingOptionsOfSubContainers() {
		$configurationA = new \F3\FLOW3\Configuration\Container();
		$configurationA->a->aSub = 'aaSub';
		$configurationA->c = 'c';
		$configurationB = new \F3\FLOW3\Configuration\Container();
		$configurationB->a->bSub = 'abSub';
		$configurationB->d = 'd';

		$expectedConfiguration = new \F3\FLOW3\Configuration\Container();
		$expectedConfiguration->a->aSub = 'aaSub';
		$expectedConfiguration->c = 'c';
		$expectedConfiguration->a->bSub = 'abSub';
		$expectedConfiguration->d = 'd';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeCanMergeTwoContainersRecursivelyWithConflictingOptions() {
		$configurationA = new \F3\FLOW3\Configuration\Container();
		$configurationA->a->aSub = 'oldA';
		$configurationA->a->aSubB = 'oldSubB';
		$configurationA->b = 'oldB';
		$configurationB = new \F3\FLOW3\Configuration\Container();
		$configurationB->a->aSub = 'newA';
		$configurationB->b = 'newB';

		$expectedConfiguration = new \F3\FLOW3\Configuration\Container();
		$expectedConfiguration->a->aSub = 'newA';
		$expectedConfiguration->a->aSubB = 'oldSubB';
		$expectedConfiguration->b = 'newB';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeCanHandleNestedContainersWithMoreThanTwoLevels() {
		$configurationA = new \F3\FLOW3\Configuration\Container();
		$configurationA->a->aa->aaa = 'oldAAA';
		$configurationA->a->ab = 'oldAB';
		$configurationA->a->aa->aab->aaba->aabaa = 'oldAABAA';
		$configurationA->b = 'oldB';

		$configurationB = new \F3\FLOW3\Configuration\Container();
		$configurationB->a->aa->aaa = 'newAAA';
		$configurationB->a->aa->aab->aabb = 'newAABB';

		$expectedConfiguration = new \F3\FLOW3\Configuration\Container();
		$expectedConfiguration->a->aa->aaa = 'newAAA';
		$expectedConfiguration->a->ab = 'oldAB';
		$expectedConfiguration->a->aa->aab->aaba->aabaa = 'oldAABAA';
		$expectedConfiguration->a->aa->aab->aabb = 'newAABB';
		$expectedConfiguration->b = 'oldB';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function mergeDoesNotTryToMergeAContainerWithAnArray() {
		$configurationA = new \F3\FLOW3\Configuration\Container();
		$configurationA->parent->children = array('a' => 'A');

		$configurationB = new \F3\FLOW3\Configuration\Container();
		$configurationB->parent->children->a = 'A';

		$expectedConfiguration = new \F3\FLOW3\Configuration\Container();
		$expectedConfiguration->parent->children->a = 'A';

		$this->assertEquals($expectedConfiguration, $configurationA->mergeWith($configurationB), 'The merge result is not as expected.');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \F3\FLOW3\Configuration\Exception
	 */
	public function callingNonExistingMethodResultsInException() {
		$configuration = new \F3\FLOW3\Configuration\Container();

		$configuration->nonExistingMethod();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \F3\FLOW3\Configuration\Exception
	 */
	public function passingNoArgumentToMagicSetterResultsInException() {
		$configuration = new \F3\FLOW3\Configuration\Container();

		$configuration->setOption();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @expectedException \F3\FLOW3\Configuration\Exception
	 */
	public function passingTwoArgumentToMagicSetterResultsInException() {
		$configuration = new \F3\FLOW3\Configuration\Container();

		$configuration->setOption('argument1', 'argument2');
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function simpleOptionCanBeAddedThroughMagicSetter() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->setNewOption('testValue');
		$this->assertEquals('testValue', $configuration->newOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyThroughMagicSetter() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->parentOption->setChildOption('the child');
		$this->assertEquals('the child', $configuration->parentOption->childOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function cascadedOptionCanBeCreatedOnTheFlyOnThirdLevelThroughMagicSetter() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration->parentOption->childOption->setGrandChildOption('the grand child');
		$this->assertEquals('the grand child', $configuration->parentOption->childOption->grandChildOption);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function magicSetterReturnsItself() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$this->assertSame($configuration, $configuration->setNewOption('testValue'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function optionsCanBeAddedThroughChainingSyntax() {
		$configuration = new \F3\FLOW3\Configuration\Container();
		$configuration
			->setOption1('value1')
			->setOption2('value2')
			->setOption3('value3');
		$this->assertEquals('value1', $configuration->option1);
		$this->assertEquals('value2', $configuration->option2);
		$this->assertEquals('value3', $configuration->option3);
	}
}
?>