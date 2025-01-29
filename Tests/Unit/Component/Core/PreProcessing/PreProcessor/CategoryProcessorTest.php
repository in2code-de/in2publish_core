<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\PreProcessing\PreProcessor;

use In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\CategoryProcessor;
use In2code\In2publishCore\Component\Core\PreProcessing\Service\TcaEscapingMarkerService;
use In2code\In2publishCore\Component\Core\Resolver\SelectMmResolver;
use In2code\In2publishCore\Component\Core\Service\Config\ExcludedTablesService;
use In2code\In2publishCore\Tests\UnitTestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor\CategoryProcessor
 */
class CategoryProcessorTest extends UnitTestCase
{
    /**
     * @covers ::process
     * @covers ::buildResolver
     */
    public function testProcessResultIsCompatibleForAnyTca(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $categoryProcessor = new CategoryProcessor($container);

        $tca = [];

        $additionalWhereBefore = '{#sys_category}.{#sys_language_uid} IN (-1, 0)
             AND {#sys_category_record_mm}.{#fieldname} = "categories"
             AND {#sys_category_record_mm}.{#tablenames} = "table_foo"';

        $additionalWhereAfter = '"sys_category"."sys_language_uid" IN (-1, 0)
             AND "sys_category_record_mm"."{#"fieldname" = "categories"
             AND "sys_category_record_mm"."tablenames" = "table_foo"';

        $tcaEscapingMarkerService = $this->createMock(TcaEscapingMarkerService::class);
        $tcaEscapingMarkerService->expects($this->once())
                                 ->method('escapeMarkedIdentifier')
                                 ->with($additionalWhereBefore)
                                 ->willReturn($additionalWhereAfter);

        $categoryProcessor->injectTcaEscapingMarkerService($tcaEscapingMarkerService);

        $excludedTablesService = $this->createMock(ExcludedTablesService::class);
        $excludedTablesService->method('isExcludedTable')->willReturn(false);
        $categoryProcessor->injectExcludedTablesService($excludedTablesService);

        $resolver = $this->createMock(SelectMmResolver::class);
        $resolver->expects($this->once())->method('configure')->with(
            $additionalWhereAfter,
            'categories',
            'sys_category_record_mm',
            'sys_category',
            'uid_foreign',
        );


        $container->method('get')->willReturn($resolver);

        $localDatabase = $this->createMock(Connection::class);
        $localDatabase->method('quote')->willReturn('"table_foo"');
        $categoryProcessor->injectLocalDatabase($localDatabase);

        $result = $categoryProcessor->process('table_foo', 'categories', $tca);
        $this->assertTrue($result->isCompatible());
    }
}
