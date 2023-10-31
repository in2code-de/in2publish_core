<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Publisher\Command;

use In2code\In2publishCore\CommonInjection\LocalDatabaseInjection;
use In2code\In2publishCore\Component\Core\FileHandling\Service\FalDriverServiceInjection;
use In2code\In2publishCore\Component\Core\Publisher\Instruction\PublishInstruction;
use In2code\In2publishCore\Service\Context\ContextServiceInjection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        $this->addArgument('requestToken', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $requestToken = $input->getArgument('requestToken');

        $query = $this->localDatabase->createQueryBuilder();
        $query->select('*')
              ->from('tx_in2publishcore_filepublisher_instruction')
              ->where($query->expr()->eq('request_token', $query->createNamedParameter($requestToken)));
        $result = $query->execute();
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
