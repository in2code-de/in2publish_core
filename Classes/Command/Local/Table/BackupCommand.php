<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Command\Local\Table;

/*
 * Copyright notice
 *
 * (c) 2021 in2code.de and the following authors:
 * Oliver Eglseder <oliver.eglseder@in2code.de>
 *
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 */

use In2code\In2publishCore\Utility\DatabaseUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;

class BackupCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const ARG_TABLE_NAME = 'tableName';
    public const ARG_TABLE_NAME_DESCRIPTION = 'The table to back up';
    public const IDENTIFIER = 'in2publish_core:table:backup';

    /** @var Connection */
    protected $localDatabase;

    public function __construct(Connection $localDatabase, string $name = null)
    {
        parent::__construct($name);
        $this->localDatabase = $localDatabase;
    }

    protected function configure(): void
    {
        $this->addArgument(self::ARG_TABLE_NAME, InputArgument::REQUIRED, self::ARG_TABLE_NAME_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getArgument(self::ARG_TABLE_NAME);
        $this->logger->notice('Called Backup Table Command for table "' . $tableName . '"');
        DatabaseUtility::backupTable($this->localDatabase, $tableName);
        return 0;
    }
}
