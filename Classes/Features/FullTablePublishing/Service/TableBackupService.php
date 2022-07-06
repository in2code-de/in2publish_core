<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\FullTablePublishing\Service;

use In2code\In2publishCore\Config\ConfigContainer;
use RuntimeException;
use Throwable;
use TYPO3\CMS\Core\Database\Connection;
use ZipArchive;

use function array_reverse;
use function array_slice;
use function class_exists;
use function date;
use function fopen;
use function fwrite;
use function glob;
use function implode;
use function is_array;
use function is_string;
use function rtrim;
use function time;
use function unlink;

class TableBackupService
{
    private ConfigContainer $configContainer;

    public function injectConfigContainer(ConfigContainer $configContainer): void
    {
        $this->configContainer = $configContainer;
    }

    public function createBackup(Connection $connection, string $table): void
    {
        $backupSettings = $this->configContainer->get('backup.publishTableCommand');
        $keepBackups = $backupSettings['keepBackups'];
        $backupLocation = $backupSettings['backupLocation'];
        $addDropTable = $backupSettings['addDropTable'];
        $zipBackup = $backupSettings['zipBackup'];

        if ($zipBackup && !class_exists(ZipArchive::class)) {
            throw new RuntimeException(
                'ZipArchive is not available. Please install zip extension or disable the option backup.publishTableCommand.zipBackup.',
                1657106012
            );
        }

        $dumpFileName = rtrim($backupLocation, '/') . '/' . time() . '_' . $table . '.sql';
        $resource = fopen($dumpFileName, 'xb');
        if (false === $resource) {
            throw new RuntimeException('Could not create backup file', 1657105400);
        }

        $this->removeStaleBackups($backupLocation, $table, $keepBackups);
        $this->dumpTable($connection, $table, $resource, $addDropTable);
        $this->compressBackup($zipBackup, $dumpFileName);
    }

    protected function removeStaleBackups(string $backupLocation, string $table, int $keepBackups): void
    {
        $backups = glob($backupLocation . '/*_' . $table . '.*');

        if (!is_array($backups)) {
            return;
        }
        $backups = array_reverse($backups);
        $backupsToDelete = array_slice($backups, $keepBackups);
        foreach ($backupsToDelete as $backupToDelete) {
            unlink($backupToDelete);
        }
    }

    /**
     * @throws Throwable
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function dumpTable(Connection $connection, string $table, $resource, bool $addDropTable): void
    {
        $this->dumpHeader($table, $resource);
        $this->dumpDropTableStatement($table, $addDropTable, $resource);
        $this->dumpCreateTableStatement($connection, $table, $resource);
        $this->dumpTableData($connection, $table, $resource);
    }

    protected function dumpHeader(string $table, $resource): void
    {
        $date = date('d.m.Y H:i:s');
        fwrite(
            $resource,
            <<<SQL
/*---------------------------------------------------------------
 * Date: $date
 * Table: "$table"
 * Writtern by: in2publish_core MySQL/MariaDB exporter (TableBackupService)
 *---------------------------------------------------------------*/

SQL
        );
    }

    protected function dumpDropTableStatement(string $table, bool $addDropTable, $resource): void
    {
        if ($addDropTable === true) {
            fwrite($resource, "DROP TABLE IF EXISTS $table;\n");
        }
    }

    protected function dumpCreateTableStatement(Connection $connection, string $table, $resource): void
    {
        $schemaQuery = $connection->executeQuery('SHOW CREATE TABLE ' . $table);
        $result = $schemaQuery->fetchAllAssociative();

        $createTable = $result[0]['Create Table'];
        fwrite($resource, "$createTable;\n");
    }

    protected function dumpTableData(Connection $connection, string $table, $resource): void
    {
        $query = $connection->createQueryBuilder();
        $query->getRestrictions()->removeAll();
        $resultSet = $query->select('*')->from($table)->execute();

        $escapedTableName = $connection->quoteIdentifier($table);

        while ($row = $resultSet->fetchAssociative()) {
            foreach ($row as $index => $value) {
                if (!empty($value) && is_string($value)) {
                    $row[$index] = $connection->quote($value);
                }
            }
            $values = implode(',', $row);
            fwrite($resource, "INSERT INTO $escapedTableName VALUES ($values)\n");
        }
    }

    protected function compressBackup($zipBackup, string $dumpFileName): void
    {
        if ($zipBackup === true) {
            $zipFileName = $dumpFileName . '.zip';
            $zip = new ZipArchive();
            if ($zip->open($zipFileName, ZipArchive::CREATE) === true) {
                $zip->addFile($dumpFileName);
                $backupWritten = $zip->close();
                if (true !== $backupWritten) {
                    throw new RuntimeException('Could not create zip from backup file', 1657106115);
                }
                unlink($dumpFileName);
            }
        }
    }
}
