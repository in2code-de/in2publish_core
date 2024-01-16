<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Functional\Component\Core\DemandResolver\SysRedirect;

use In2code\In2publishCore\Component\Core\Demand\DemandsCollection;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\DemandResolver\SysRedirect\SysRedirectSelectDemandResolver;
use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\RecordCollection;
use In2code\In2publishCore\Tests\FunctionalTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function reset;

class SysRedirectSelectDemandResolverTest extends FunctionalTestCase
{
    // Read-only tests do not require a database reset
    protected bool $initializeDatabase = false;

    public function testSysRedirectSelectDemandResolverResolvesSysRedirectRelation(): void
    {
        $pageRecord = new DatabaseRecord('pages', 170, ['uid' => 170], ['uid' => 170], []);

        $demands = new DemandsCollection();
        $demands->addDemand(
            new SysRedirectDemand(
                'sys_redirect',
                'source_path = "/19-singlerecordpublishing"',
                $pageRecord,
            ),
        );
        $recordCollection = new RecordCollection();

        $sysRedirectDemandResolver = GeneralUtility::makeInstance(SysRedirectSelectDemandResolver::class);
        $sysRedirectDemandResolver->resolveDemand($demands, $recordCollection);

        self::assertTrue($recordCollection->contains('sys_redirect', ['uid' => 20]));

        $sysRedirectRecord = $recordCollection->getRecord('sys_redirect', 20);
        $sysRedirectParents = $sysRedirectRecord->getParents();
        self::assertCount(1, $sysRedirectParents);
        $sysRedirectParent = reset($sysRedirectParents);
        self::assertSame($pageRecord, $sysRedirectParent);
    }
}
