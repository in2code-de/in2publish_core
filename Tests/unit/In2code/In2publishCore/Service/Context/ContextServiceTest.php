<?php
declare(strict_types=1);
namespace In2code\In2publishCore\Tests\In2code\In2publishCore\Service\Context;

/*
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
 */

use Codeception\Test\Unit;
use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTester;
use LogicException;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Context\ContextService
 */
class ContextServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $this->tester->setUp();
        $this->tester->clearIn2publishContext();
    }

    protected function _after()
    {
        $this->tester->tearDown();
        $this->tester->clearIn2publishContext();
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     * @covers ::getContext
     */
    public function testDefaultContextIsForeign()
    {
        $this->tester->setIn2publishContext(null);

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
        $this->tester->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertSame(ContextService::LOCAL, $contextService->getContext());
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     *
     * @expectedException LogicException
     * @expectedExceptionCode 1469717011
     * @expectedExceptionMessage The defined in2publish context is not supported
     */
    public function testUnsupportedContextWillThrowAnExceptionIfApplicationContextIsNotProduction()
    {
        $this->tester->setIn2publishContext('Wrong');
        $this->tester->setApplicationContext('Development');

        new ContextService();
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     */
    public function testUnsupportedContextResultsInDefaultContextIfApplicationContextIsProduction()
    {
        $this->tester->setIn2publishContext('Wrong');
        $this->tester->setApplicationContext('Production');

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsTrueIfIn2publishContextIsLocal()
    {
        $this->tester->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsFalseIfIn2publishContextIsNotLocal()
    {
        $this->tester->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsTrueIfIn2publishContextIsForeign()
    {
        $this->tester->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsFalseIfIn2publishContextIsLocal()
    {
        $this->tester->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsFalseIfContextIsNotDefined()
    {
        $this->tester->setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isContextDefined());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsTrueIfContextIsDefined()
    {
        $this->tester->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testRedirectContextAlsoDefinesContext()
    {
        $this->tester->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsTrueIfRedirectIn2publishContextIsLocal()
    {
        $this->tester->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsTrueIfRedirectIn2publishContextIsForeign()
    {
        $this->tester->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsFalseIfRedirectIn2publishContextIsForeign()
    {
        $this->tester->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsFalseIfRedirectIn2publishContextIsLocal()
    {
        $this->tester->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }
}
