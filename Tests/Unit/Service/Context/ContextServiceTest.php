<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Service\Context;

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

use In2code\In2publishCore\Service\Context\ContextService;
use In2code\In2publishCore\Tests\UnitTestCase;
use LogicException;

use ReflectionProperty;
use TYPO3\CMS\Core\Core\ApplicationContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function putenv;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Context\ContextService
 */
class ContextServiceTest extends UnitTestCase
{
    private function setRedirectedIn2publishContext($value): void
    {
        $this->setIn2publishContext(null);
        if (null === $value) {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME);
        } else {
            putenv(ContextService::REDIRECT_ENV_VAR_NAME . '=' . $value);
        }
    }

    private function setIn2publishContext($value): void
    {
        if (null === $value) {
            putenv(ContextService::ENV_VAR_NAME);
        } else {
            putenv(ContextService::ENV_VAR_NAME . '=' . $value);
        }
    }

    private function setApplicationContext($value)
    {
        if (null === $value) {
            putenv('TYPO3_CONTEXT');
            $applicationContext = new ApplicationContext('Production');
        } else {
            putenv('TYPO3_CONTEXT=' . $value);
            $applicationContext = new ApplicationContext($value);
        }

        $reflectionProperty = new ReflectionProperty(GeneralUtility::class, 'applicationContext');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($applicationContext);

        $environmentReflection = new ReflectionProperty(Environment::class, 'context');
        $environmentReflection->setAccessible(true);
        $environmentReflection->setValue(Environment::class, new ApplicationContext($value));
    }


    /**
     * @covers ::__construct
     * @covers ::determineContext
     * @covers ::getContext
     */
    public function testDefaultContextIsForeign(): void
    {
        $this->setIn2publishContext(null);

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
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertSame(ContextService::LOCAL, $contextService->getContext());
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     */
    public function testUnsupportedContextWillThrowAnExceptionIfApplicationContextIsNotProduction()
    {
        $this->setIn2publishContext('Wrong');
        $this->setApplicationContext('Development');

        $this->expectException(LogicException::class);
        $this->expectExceptionCode(1469717011);
        $this->expectExceptionMessage('The defined in2publish context is not supported');

        new ContextService();
    }

    /**
     * @covers ::__construct
     * @covers ::determineContext
     */
    public function testUnsupportedContextResultsInDefaultContextIfApplicationContextIsProduction()
    {
        $this->setIn2publishContext('Wrong');
        $this->setApplicationContext('Production');

        $contextService = new ContextService();
        $this->assertSame(ContextService::FOREIGN, $contextService->getContext());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsTrueIfIn2publishContextIsLocal()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsFalseIfIn2publishContextIsNotLocal()
    {
        $this->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsTrueIfIn2publishContextIsForeign()
    {
        $this->setIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsFalseIfIn2publishContextIsLocal()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsFalseIfContextIsNotDefined()
    {
        $this->setIn2publishContext(null);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isContextDefined());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testIsContextDefinedReturnsTrueIfContextIsDefined()
    {
        $this->setIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    /**
     * @covers ::isContextDefined
     */
    public function testRedirectContextAlsoDefinesContext()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isContextDefined());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsTrueIfRedirectIn2publishContextIsLocal()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsTrueIfRedirectIn2publishContextIsForeign()
    {
        $this->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertTrue($contextService->isForeign());
    }

    /**
     * @covers ::isLocal
     */
    public function testIsLocalReturnsFalseIfRedirectIn2publishContextIsForeign()
    {
        $this->setRedirectedIn2publishContext(ContextService::FOREIGN);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isLocal());
    }

    /**
     * @covers ::isForeign
     */
    public function testIsForeignReturnsFalseIfRedirectIn2publishContextIsLocal()
    {
        $this->setRedirectedIn2publishContext(ContextService::LOCAL);

        $contextService = new ContextService();
        $this->assertFalse($contextService->isForeign());
    }
}
