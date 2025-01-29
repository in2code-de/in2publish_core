<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Service\Configuration;

use In2code\In2publishCore\Component\ConfigContainer\ConfigContainer;
use In2code\In2publishCore\Service\Configuration\IgnoredFieldsService;
use In2code\In2publishCore\Tests\UnitTestCase;

/**
 * @coversDefaultClass \In2code\In2publishCore\Service\Configuration\IgnoredFieldsService
 */
class IgnoredFieldsServiceTest extends UnitTestCase
{
    public function testGetIgnoredFields(): void
    {
        $configContainerMock = $this->createMock(ConfigContainer::class);
        $configContainerMock->method('get')->with('ignoredFields')->willReturn([
            'foo' => [
                'fields' => [
                    'rowDescription',
                ],
            ],
        ]);

        $service = new IgnoredFieldsService($configContainerMock);

        $ignoredFields = $service->getIgnoredFields('foo');

        self::assertSame(['rowDescription'], $ignoredFields);
    }

    public function testGetIgnoredFieldsProcessesCtrl(): void
    {
        $configContainerMock = $this->createMock(ConfigContainer::class);
        $configContainerMock->method('get')->with('ignoredFields')->willReturn([
            '.*' => [
                'ctrl' => [
                    'tstamp',
                    'versioningWS',
                    'transOrigDiffSourceField',
                ],
            ],
            'foo' => [
                'fields' => [
                    'rowDescription',
                ],
            ],
        ]);

        $service = new IgnoredFieldsService($configContainerMock);

        $GLOBALS['TCA']['foo']['ctrl']['tstamp'] = 'timestamp';
        $GLOBALS['TCA']['foo']['ctrl']['versioningWS'] = true;
        $GLOBALS['TCA']['foo']['ctrl']['transOrigDiffSourceField'] = 'l10_src';

        $ignoredFields = $service->getIgnoredFields('foo');

        self::assertSame(
            ['timestamp', 't3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage', 'l10_src', 'rowDescription'],
            $ignoredFields,
        );
    }
}
