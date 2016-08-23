<?php
namespace In2code\In2publishCore\Tests\Unit\Service\Context;

/***************************************************************
 * Copyright notice
 *
 * (c) 2016 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\Helper\TestingHelper;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Context\ContextService
 */
class ContextServiceTest extends UnitTestCase
{
    /**
     * @covers ::__construct
     * @covers ::determineContext
     * @covers ::getContext
     */
    public function testDefaultContextIsForeign()
    {
        TestingHelper::setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     * @covers ::getContext
     */
    public function testContextIsDeterminedByEnvironmentVariable()
    {
        TestingHelper::setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertSame(ContextService::LOCAL, $contextService->getContext());
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     */
    public function testUnsupportedContextWillThrowAnExceptionIfApplicationContextIsNotProduction()
    {
        TestingHelper::setIn2publishContext('Wrong');
        TestingHelper::setApplicationContext('Development');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The defined in2publish context is not supported');
        $this->expectExceptionCode(1469717011);
        new ContextService();
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     */
    public function testUnsupportedContextResultsInDefaultContextIfApplicationContextIsProduction()
    {
        TestingHelper::setIn2publishContext('Wrong');
        TestingHelper::setApplicationContext('Production');

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsTrueIfIn2publishContextIsLocal()
    {
        TestingHelper::setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsFalseIfIn2publishContextIsNotLocal()
    {
        TestingHelper::setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsTrueIfIn2publishContextIsForeign()
    {
        TestingHelper::setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsFalseIfIn2publishContextIsLocal()
    {
        TestingHelper::setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsFalseIfContextIsNotDefined()
    {
        TestingHelper::setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isContextDefined());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsTrueIfContextIsDefined()
    {
        TestingHelper::setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

}
