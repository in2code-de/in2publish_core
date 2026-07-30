<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\DemandResolver\Join;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\Join\JoinDemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function reset;

class JoinDemandResolverTest extends FunctionalTestCase
{
    // Read-only tests do not require a database reset
    protected bool $initializeDatabase = true;

    public function testJoinDemandResolverResolvesMmRelation(): void
    {
        $pageRecord = new DatabaseRecord('pages', 470, ['uid' => 470], ['uid' => 470], []);

        $demands = new DemandsCollection();
        $demands->addDemand(
            new JoinDemand('sys_category_record_mm', 'sys_category', '', 'uid_foreign', 470, $pageRecord),
        );
        $recordCollection = new RecordCollection();

        $joinDemandResolver = GeneralUtility::makeInstance(JoinDemandResolver::class);
        $joinDemandResolver->resolveDemand($demands, $recordCollection);
        self::assertTrue($recordCollection->contains('sys_category', ['uid' => 3]));

        $sysCategoryRecord = $recordCollection->getRecord('sys_category', 3);
        $sysCategoryParents = $sysCategoryRecord->getParents();
        self::assertCount(1, $sysCategoryParents);
        $sysCategoryMmRecord = reset($sysCategoryParents);
        self::assertSame('6e04e475f8bd1c56c0b8ec573e3014ebac9a19be', $sysCategoryMmRecord->getId());
        $mmParents = $sysCategoryMmRecord->getParents();
        self::assertCount(1, $mmParents);
        $mmParent = reset($mmParents);
        self::assertSame($pageRecord, $mmParent);
    }
}
