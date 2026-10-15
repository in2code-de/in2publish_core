<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Testing\Tests\Database;

use In2code\In2publishCore\Testing\Tests\Database\FileUidSequenceTest;
use In2code\In2publishCore\Testing\Tests\TestResult;
use In2code\In2publishCore\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use TYPO3\CMS\Core\Database\Connection;

#[CoversClass(FileUidSequenceTest::class)]
class FileUidSequenceTestTest extends UnitTestCase
{
    public function testDisabledOrAcceptedCheckDoesNotQueryEitherDatabase(): void
    {
        self::assertSame(
            TestResult::SKIPPED,
            FileUidSequenceTest::check(false, false)->getSeverity(),
        );
        self::assertSame(
            TestResult::SKIPPED,
            FileUidSequenceTest::check(true, true)->getSeverity(),
        );
    }

    public static function sequences(): array
    {
        return [
            'empty tables' => [1, 1, TestResult::OK],
            'equal sequences' => [42, 42, TestResult::OK],
            'local ahead' => [43, 42, TestResult::OK],
            'foreign ahead even if files were deleted' => [42, 43, TestResult::WARNING],
            'database strings' => ['42', '43', TestResult::WARNING],
            'local sequence unavailable' => [null, 43, TestResult::WARNING],
            'foreign table missing' => [42, false, TestResult::WARNING],
        ];
    }

    #[DataProvider('sequences')]
    public function testComparesAutoIncrementRatherThanRecordCount(
        int|string|null|bool $local,
        int|string|null|bool $foreign,
        string $expected
    ): void {
        $localDatabase = $this->createMock(Connection::class);
        $foreignDatabase = $this->createMock(Connection::class);
        foreach ([[$localDatabase, $local], [$foreignDatabase, $foreign]] as [$connection, $value]) {
            $connection->expects(self::once())->method('executeStatement')->with(
                '/*!80000 SET SESSION information_schema_stats_expiry = 0 */',
            );
            $connection->expects(self::once())->method('fetchOne')->with(
                'SELECT AUTO_INCREMENT FROM information_schema.TABLES '
                . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                ['sys_file'],
            )->willReturn($value);
        }
        self::assertSame(
            $expected,
            FileUidSequenceTest::check(true, false, $localDatabase, $foreignDatabase)->getSeverity(),
        );
    }
}
