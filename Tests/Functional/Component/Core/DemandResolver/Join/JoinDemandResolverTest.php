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
        $pageRecord = new DatabaseRecord('pages', 77, ['uid' => 77], ['uid' => 77], []);

        $demands = new DemandsCollection();
        $demands->addDemand(
            new JoinDemand('sys_category_record_mm', 'sys_category', '', 'uid_foreign', 77, $pageRecord),
        );
        $recordCollection = new RecordCollection();

        $joinDemandResolver = GeneralUtility::makeInstance(JoinDemandResolver::class);
        $joinDemandResolver->resolveDemand($demands, $recordCollection);
        self::assertTrue($recordCollection->contains('sys_category', ['uid' => 1]));

        $sysCategoryRecord = $recordCollection->getRecord('sys_category', 1);
        $sysCategoryParents = $sysCategoryRecord->getParents();
        self::assertCount(1, $sysCategoryParents);
        $sysCategoryMmRecord = reset($sysCategoryParents);
        self::assertSame('6ed754e2fa2e937739af04a069a6f6d9e96db685', $sysCategoryMmRecord->getId());
        $mmParents = $sysCategoryMmRecord->getParents();
        self::assertCount(1, $mmParents);
        $mmParent = reset($mmParents);
        self::assertSame($pageRecord, $mmParent);
    }
}
