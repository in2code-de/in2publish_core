<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\DemandResolver\Select;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\Select\SelectDemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\PageTreeRootRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function reset;

class SelectDemandResolverTest extends FunctionalTestCase
{
    // Read-only tests do not require a database reset
    protected bool $initializeDatabase = false;

    public function testSelectDemandResolverFindsRequestedRelations(): void
    {
        $rootRecord = new PageTreeRootRecord();

        $demands = new DemandsCollection();
        $demands->addDemand(
            new SelectDemand(
                'pages',
                'title LIKE "%languageIndependentWorkflows%"',
                'uid',
                16,
                $rootRecord,
            ),
        );
        $recordCollection = new RecordCollection();

        $selectDemandResolver = GeneralUtility::makeInstance(SelectDemandResolver::class);
        $selectDemandResolver->resolveDemand($demands, $recordCollection);

        self::assertTrue($recordCollection->contains('pages', ['uid' => 16]));

        $pageRecord = $recordCollection->getRecord('pages', 16);
        $pageParents = $pageRecord->getParents();
        self::assertCount(1, $pageParents);
        $pageParent = reset($pageParents);
        self::assertSame($rootRecord, $pageParent);
    }
}
