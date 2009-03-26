<?php
declare(ENCODING = 'utf-8');
namespace F3\FLOW3\Validation\Validator;

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
 * Testcase for the regular expression validator
 *
 * @package FLOW3
 * @subpackage Tests
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RegularExpressionValidatorTest extends \F3\Testing\BaseTestCase {

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');

		$regularExpressionValidator = new \F3\FLOW3\Validation\Validator\RegularExpressionValidator();
		$regularExpressionValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$this->assertTrue($regularExpressionValidator->isValid('simple1expression', $validationErrors, array('regularExpression' => '/^simple[0-9]expression$/')));
		$this->assertFalse($regularExpressionValidator->isValid('simple1expressions', $validationErrors, array('regularExpression' => '/^simple[0-9]expression$/')));
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorObjectIfTheExpressionDidNotMatch() {
		$mockObjectFactory = $this->getMock('F3\FLOW3\Object\FactoryInterface');
		$mockObjectFactory->expects($this->any())->method('create')->with('F3\FLOW3\Validation\Error', 'The regular expression was empty.', 1221565132);

		$regularExpressionValidator = new \F3\FLOW3\Validation\Validator\RegularExpressionValidator('/^simple[0-9]expression$/');
		$regularExpressionValidator->injectObjectFactory($mockObjectFactory);
		$validationErrors = new \F3\FLOW3\Validation\Errors();

		$regularExpressionValidator->isValid('some subject that will not match', $validationErrors);
	}
}

?>