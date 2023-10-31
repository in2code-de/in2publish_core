<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\ExtNewsRelatedProcessor;
use In2code\In2publishCore\Component\Core\Resolver\StaticJoinResolver;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\ExtNewsRelatedProcessor
 */
class ExtNewsRelatedProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testExtRelatedNewsProcessReturnsStaticJoinResolver(): void
    {
        $extRelatedProcessor = new ExtNewsRelatedProcessor();

        $tca = [];

        $container = $this->createMock(ContainerInterface::class);
        $staticJoinResolver = $this->createMock(StaticJoinResolver::class);
        $staticJoinResolver->expects($this->once())->method('configure')->with(
            'tx_news_domain_model_news_related_mm',
            'tx_news_domain_model_news',
            '',
            'uid_foreign',
        );
        $container->expects($this->once())->method('get')->willReturn($staticJoinResolver);
        $extRelatedProcessor->injectContainer($container);

        $result = $extRelatedProcessor->process('table_foo', 'field_foo', $tca);
        $this->assertTrue($result->isCompatible());
    }

    /**
     * @covers ::getTable
     */
    public function testGetTable(): void
    {
        $extRelatedProcessor = new ExtNewsRelatedProcessor();
        $this->assertSame('tx_news_domain_model_news', $extRelatedProcessor->getTable());
    }

    /**
     * @covers ::getColumn
     */
    public function testGetColumn(): void
    {
        $extRelatedProcessor = new ExtNewsRelatedProcessor();
        $this->assertSame('related', $extRelatedProcessor->getColumn());
    }
}
