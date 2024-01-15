<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use Doctrine\DBAL\Result;
use In2code\In2publishCore\Command\Foreign\Status\DbConfigTestCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

use const PHP_EOL;

/**
 * @coversDefaultClass \In2code\In2publishCore\Command\Foreign\Status\DbConfigTestCommand
 */
class DbConfigTestCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     * @covers ::execute
     */
    public function testCommandCanBeExecuted(): void
    {
        $query = $this->createMock(Result::class);
        $query->method('fetchAllAssociative')->willReturn([
            [
                'configuration' => 'fii',
            ],
            [
                'configuration' => 'faa',
            ],
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('executeQuery')->willReturn($query);

        $connection = $this->createMock(Connection::class);
        $connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new DbConfigTestCommand();
        $command->injectLocalDatabase($connection);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('DB Config: WyJmaWkiLCJmYWEiXQ==' . PHP_EOL, $output->fetch());
    }
}
