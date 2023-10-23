<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Component\Core\Resolver;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Component\Core\Resolver\FlexResolver;
use In2code\In2publishCore\Component\Core\Resolver\SelectResolver;
use In2code\In2publishCore\Component\Core\Service\ResolverService;
use In2code\In2publishCore\Tests\UnitTestCase;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Service\FlexFormService;

/**
 * @coversDefaultClass \In2code\In2publishCore\Component\Core\Resolver\FlexResolver
 */
class FlexResolverTest extends UnitTestCase
{
    /**
     * @covers ::getTargetTables
     */
    public function testTargetTables(): void
    {
        // all tables in TCA
        $GLOBALS['TCA']['table_foo'] = [];
        $GLOBALS['TCA']['table_bar'] = [];

        $flexResolver = new FlexResolver();
        $expectedTargetTables = ['table_foo', 'table_bar'];
        $this->assertSame($expectedTargetTables, $flexResolver->getTargetTables());
        $this->assertArrayNotHasKey('table_bar', $flexResolver->getTargetTables());

        unset($GLOBALS['TCA']['table_foo']);
        unset($GLOBALS['TCA']['table_bar']);
    }

    /**
     * @covers ::configure
     */
    public function testConfigure(): void
    {
        $flexResolver = new FlexResolver();
        $table = new \ReflectionProperty(FlexResolver::class, 'table');
        $column = new \ReflectionProperty(FlexResolver::class, 'column');
        $processedTca = new \ReflectionProperty(FlexResolver::class, 'processedTca');
        $table->setAccessible(true);
        $column->setAccessible(true);
        $processedTca->setAccessible(true);

        $flexResolver->configure('table_foo', 'column_foo', ['tca_key1' => 'tca_value1']);
        $this->assertSame('table_foo', $table->getValue($flexResolver));
        $this->assertSame('column_foo', $column->getValue($flexResolver));
        $this->assertSame(['tca_key1' => 'tca_value1'], $processedTca->getValue($flexResolver));
    }

    /**
     * @covers ::resolve
     */
    public function testResolveDoesNotResolveFileRecords()
    {
        $flexResolver = new FlexResolver();
        $demands = new DemandsCollection();
        $fileRecord = $this->createMock(FileRecord::class);

        $flexResolver->resolve($demands, $fileRecord);

        $fileRecord->expects($this->never())->method('getLocalProps');
        $fileRecord->expects($this->never())->method('getForeignProps');
    }

    /**
     * @covers ::resolve
     */
    public function testResolveDoesNotGetResolverOnEmptyDatabaseRecord(): void
    {
        $emptyDatabaseRecord = new DatabaseRecord('table_foo', 42, [], [], []);

        $flexResolver = $this->createConfiguredFlexResolver();
        $flexFormTools = $this->createMock(FlexFormTools::class);
        $resolversService = $this->createMock(ResolverService::class);

        $dataStructure = json_encode(
            [
                'type' => 'record',
                'dataStructureKey' => 'column_foo',
            ],
        );

        $flexFormTools->method('getDataStructureIdentifier')->willReturn($dataStructure);
        $flexFormService = $this->createMock(FlexFormService::class);
        $flexFormService->method('convertFlexFormContentToArray')->willReturn([]);
        $resolversService->expects($this->never())->method('getResolversForTable');

        $flexResolver->injectFlexFormTools($flexFormTools);
        $flexResolver->injectFlexFormService($flexFormService);
        $flexResolver->injectResolverService($resolversService);

        $demands = new DemandsCollection();

        $flexResolver->resolve($demands, $emptyDatabaseRecord);
    }

    /**
     * @covers ::resolve
     */
    public function testResolveDelegatesResolveToItsResolvers(): void
    {
        $databaseRecord = new DatabaseRecord('table_foo', 43, ['pid' => 7, 'column_foo' => 'value_foo'], [], []);

        $processedTca = ['tca_key1' => 'tca_value1'];
        $flexResolver = $this->createConfiguredFlexResolver($processedTca);
        $flexFormTools = $this->createMock(FlexFormTools::class);
        $resolversService = $this->createMock(ResolverService::class);

        $dataStructureIdentifier = [
            'type' => 'tca',
            'tableName' => 'table_foo',
            'fieldName' => 'column_foo',
            'dataStructureKey' => 'column_foo',
        ];

        $dataStructure = json_encode($dataStructureIdentifier);

        $flexFormTools->method('getDataStructureIdentifier')->willReturn($dataStructure);
        $flexFormService = $this->createMock(FlexFormService::class);
        $flexFormService->method('convertFlexFormContentToArray')->willReturn(['column_foo' => 'value_foo']);

        $selectResolver = $this->createMock(SelectResolver::class);
        $selectResolver->expects($this->once())->method('resolve');
        $resolvers = ['column_foo' => $selectResolver,];
        $resolversService->expects($this->once())->method('getResolversForTable')->willReturn($resolvers);

        $flexResolver->injectFlexFormTools($flexFormTools);
        $flexResolver->injectFlexFormService($flexFormService);
        $flexResolver->injectResolverService($resolversService);

        $demands = new DemandsCollection();

        $flexResolver->resolve($demands, $databaseRecord);
    }

    protected function createConfiguredFlexResolver($tca = ['tca_key1' => 'tca_value1']): FlexResolver
    {
        $flexResolver = new FlexResolver();
        $table = new \ReflectionProperty(FlexResolver::class, 'table');
        $column = new \ReflectionProperty(FlexResolver::class, 'column');
        $processedTca = new \ReflectionProperty(FlexResolver::class, 'processedTca');
        $table->setAccessible(true);
        $column->setAccessible(true);
        $processedTca->setAccessible(true);

        $flexResolver->configure('table_foo', 'column_foo', $tca);
        return $flexResolver;
    }

//$flexFieldTca = [
//'config' => [
//'type' => 'flex',
//'ds_pointerField' => 'flex_field',
//]
//];
//
//$dataStructure = json_encode(
//[
//'type' => 'record',
//'tableName' => 'table_foo',
//'uid' => 42,
//'fieldName' => 'flex_field',
//]);
//
//$emptyDatabaseRecord = new DatabaseRecord(
//'table_foo',
//42,
//[
//'flex_field' => 'field_value_foo',
//],
//[],
//[]
//);

}
