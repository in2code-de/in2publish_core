<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\TcaHandling\Demand;

use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsCollection;
use In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory;
use PHPUnit\Framework\TestCase;
use In2code\In2publishCore\Config\ConfigContainer;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\TcaHandling\Demand\DemandsFactory
 */
class DemandsFactoryTest extends TestCase
{
    /**
     * @covers ::createDemand
     */
    public function testBuildDemandReturnsDemandCollection(): void
    {
        $configContainer = $this->createMock(ConfigContainer::class);
        $demandFactory = new DemandsFactory();
        $demandFactory->injectConfigContainer($configContainer);
        $this->assertInstanceOf(DemandsCollection::class, $demandFactory->createDemand());
    }
}
