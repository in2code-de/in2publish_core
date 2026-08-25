<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\ViewHelpers\Record;

use In2code\In2publishCore\Component\Core\Record\Model\DatabaseRecord;
use In2code\In2publishCore\Component\Core\Record\Model\FileRecord;
use In2code\In2publishCore\Tests\UnitTestCase;
use In2code\In2publishCore\ViewHelpers\Record\GetChangedDescendantsViewHelper;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(GetChangedDescendantsViewHelper::class, 'initializeArguments')]
#[CoversMethod(GetChangedDescendantsViewHelper::class, 'render')]
#[CoversMethod(GetChangedDescendantsViewHelper::class, 'collectChangedDescendants')]
class GetChangedDescendantsViewHelperTest extends UnitTestCase
{
    public function testChangedDatabaseDescendantsAreCollectedRecursively(): void
    {
        $page = new DatabaseRecord('pages', 1, ['uid' => 1], ['uid' => 1], []);
        $fileReference = new DatabaseRecord(
            'sys_file_reference',
            10,
            ['uid' => 10, 'crdate' => 1719843334],
            ['uid' => 10, 'crdate' => 1697028376],
            [],
        );
        $file = new DatabaseRecord(
            'sys_file',
            5,
            ['uid' => 5, 'creation_date' => 1750661151],
            ['uid' => 5, 'creation_date' => 1700000000],
            [],
        );
        $physicalFile = new FileRecord(
            ['storage' => 1, 'identifier' => '/image.jpg', 'sha1' => 'local'],
            ['storage' => 1, 'identifier' => '/image.jpg', 'sha1' => 'foreign'],
        );
        $page->addChild($fileReference);
        $fileReference->addChild($file);
        $file->addChild($physicalFile);
        $file->addChild($fileReference);

        $viewHelper = new GetChangedDescendantsViewHelper();
        $viewHelper->setArguments(['record' => $page, 'maxDepth' => 8]);

        self::assertSame(
            [
                'sys_file_reference' => [10 => $fileReference],
                'sys_file' => [5 => $file],
            ],
            $viewHelper->render(),
        );
    }

    public function testRecursionStopsAtConfiguredDepthAndDoesNotEnterChildPages(): void
    {
        $page = new DatabaseRecord('pages', 1, ['uid' => 1], ['uid' => 1], []);
        $directChild = new DatabaseRecord('tt_content', 1, ['header' => 'local'], ['header' => 'foreign'], []);
        $nestedChild = new DatabaseRecord('sys_file_reference', 1, ['crdate' => 2], ['crdate' => 1], []);
        $childPage = new DatabaseRecord('pages', 2, ['title' => 'local'], ['title' => 'foreign'], []);
        $childPageContent = new DatabaseRecord('tt_content', 2, ['header' => 'local'], ['header' => 'foreign'], []);
        $page->addChild($directChild);
        $directChild->addChild($nestedChild);
        $page->addChild($childPage);
        $childPage->addChild($childPageContent);

        $viewHelper = new GetChangedDescendantsViewHelper();
        $viewHelper->setArguments(['record' => $page, 'maxDepth' => 1]);

        self::assertSame(['tt_content' => [1 => $directChild]], $viewHelper->render());
    }
}
