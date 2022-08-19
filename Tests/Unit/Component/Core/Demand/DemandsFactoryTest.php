<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Demand;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\DemandsFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Demand\DemandsFactory
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
