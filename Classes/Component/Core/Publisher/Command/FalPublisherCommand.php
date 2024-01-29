<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Command;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service\FalDriverServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\PublishInstruction;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

use function explode;
use function json_decode;

class FalPublisherCommand extends Command
{
    use LocalDatabaseInjection;
    use ContextServiceInjection;
    use FalDriverServiceInjection;

    public function isEnabled(): bool
    {
        return $this->contextService->isForeign();
    }

    protected function configure(): void
    {
        $this->addArgument('requestTokens', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $requestTokens = $input->getArgument('requestTokens');
        $requestTokens = explode(',', $requestTokens);

        $query = $this->localDatabase->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_filepublisher_instruction')
              ->where(
                  $query->expr()->in(
                      'request_token',
                      $query->createNamedParameter($requestTokens, Connection::PARAM_STR_ARRAY)
                  )
              );
        $result = $query->executeQuery();
        $rows = $result->fetchAllAssociative();

        /** @var array<PublishInstruction> $instructions */
        $instructions = [];
        foreach ($rows as $row) {
            $arguments = json_decode($row['configuration'], true, 512, JSON_THROW_ON_ERROR);
            $instructions[] = new $row['instruction'](...$arguments);
        }

        foreach ($instructions as $instruction) {
            $instruction->execute($this->falDriverService);
        }
        return Command::SUCCESS;
    }
}
