<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tests\Unit\Command\Foreign\Status;

use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ForwardCompatibility\Result as ForwardResult;
use In2code\In2publishCore\Command\Foreign\Status\DbConfigTestCommand;
use In2code\In2publishCore\Tests\UnitTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

use function class_exists;

use const PHP_EOL;

class DbConfigTestCommandTest extends UnitTestCase
{
    /**
     * @ticket https://projekte.in2code.de/issues/51213
     */
    public function testCommandCanBeExecuted(): void
    {
        if (class_exists(ForwardResult::class)) {
            $query = $this->createMock(ForwardResult::class);
        } else {
            $query = $this->createMock(Statement::class);
        }
        $query->method('fetchAll')->willReturn([
            [
                'configuration' => 'fii',
            ],
            [
                'configuration' => 'faa',
            ],
        ]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->method('execute')->willReturn($query);

        $connection = $this->createMock(Connection::class);
        $connection->method('createQueryBuilder')->willReturn($queryBuilder);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $command = new DbConfigTestCommand($connection);

        $code = $command->run($input, $output);

        $this->assertSame(0, $code);
        $this->assertSame('DB Config: WyJmaWkiLCJmYWEiXQ==' . PHP_EOL, $output->fetch());
    }
}
