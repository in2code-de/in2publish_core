<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\CompareDatabaseTool\Controller;

use Doctrine\DBAL\Driver\Connection;
use In2code\In2publishCore\Config\ConfigContainer;
use In2code\In2publishCore\Features\CompareDatabaseTool\Domain\DTO\ComparisonRequest;
use In2code\In2publishCore\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

use function array_column;
use function array_combine;
use function array_diff;
use function array_intersect;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function implode;
use function max;

class CompareDatabaseToolController extends ActionController
{
    /** @var ConfigContainer */
    protected $configContainer;

    /** @var Connection */
    protected $localDatabase;

    /** @var Connection */
    protected $foreignDatabase;

    public function __construct(
        ConfigContainer $configContainer,
        Connection $localDatabase,
        Connection $foreignDatabase
    ) {
        $this->configContainer = $configContainer;
        $this->localDatabase = $localDatabase;
        $this->foreignDatabase = $foreignDatabase;
    }

    public function indexAction(): void
    {
        $tables = $this->getAllNonExcludedTables();
        $this->view->assign('tables', array_combine($tables, $tables));
    }

    public function compareAction(ComparisonRequest $comparisonRequest = null): void
    {
        if (null === $comparisonRequest) {
            $this->redirect('index');
        }
        $allowedTables = $this->getAllNonExcludedTables();
        $requestedTables = $comparisonRequest->getTables();

        $tables = array_intersect($allowedTables, $requestedTables);

        $ignoreFieldsForDiff = $this->configContainer->get('ignoreFieldsForDifferenceView');

        $differences = [];

        foreach ($tables as $table) {
            if (!array_key_exists($table, $GLOBALS['TCA'])) {
                continue;
            }
            $tableIdentifier = $this->localDatabase->quoteIdentifier($table);
            $localResult = $this->localDatabase->executeQuery("SELECT MAX(uid) from $tableIdentifier")->fetchOne();
            $tableIdentifier = $this->foreignDatabase->quoteIdentifier($table);
            $foreignResult = $this->foreignDatabase->executeQuery("SELECT MAX(uid) from $tableIdentifier")->fetchOne();

            if (null === $localResult && null === $foreignResult) {
                continue;
            }
            if (null === $localResult && $foreignResult > 0) {
                $differences[$table]['general'][] = 'The table is empty on local';
                continue;
            }
            if ($localResult > 0 && null === $foreignResult) {
                $differences[$table]['general'][] = 'The table is empty on foreign';
                continue;
            }

            $maxUid = max($localResult, $foreignResult);

            $rest = $maxUid % 1000;
            $iterations = $maxUid / 1000;
            if ($rest > 0) {
                ++$iterations;
            }

            for ($i = 0; $i < $iterations; $i++) {
                $offset = 1000 * $i;
                $limit = 1000 * ($i + 1);

                $localQuery = $this->localDatabase->createQueryBuilder();
                $localResult = $localQuery->select('*')
                                          ->from($table)
                                          ->where(
                                              $localQuery->expr()->andX(
                                                  $localQuery->expr()->gte('uid', $offset),
                                                  $localQuery->expr()->lt('uid', $limit)
                                              )
                                          )
                                          ->execute();
                $localRows = array_column($localResult->fetchAllAssociative(), null, 'uid');
                $foreignQuery = $this->foreignDatabase->createQueryBuilder();
                $foreignResult = $foreignQuery->select('*')
                                              ->from($table)
                                              ->where(
                                                  $foreignQuery->expr()->andX(
                                                      $foreignQuery->expr()->gte('uid', $offset),
                                                      $foreignQuery->expr()->lt('uid', $limit)
                                                  )
                                              )
                                              ->execute();
                $foreignRows = array_column($foreignResult->fetchAllAssociative(), null, 'uid');

                $uidList = array_unique(array_merge(array_keys($localRows), array_keys($foreignRows)));

                foreach ($uidList as $uid) {
                    $localRowExists = array_key_exists($uid, $localRows);
                    $foreignRowExists = array_key_exists($uid, $foreignRows);
                    if ($localRowExists && $foreignRowExists) {
                        $ignoredFields = $ignoreFieldsForDiff[$table] ?? [];

                        $localRow = $localRows[$uid];
                        $foreignRow = $foreignRows[$uid];
                        $localRow = ArrayUtility::removeFromArrayByKey($localRow, $ignoredFields);
                        $foreignRow = ArrayUtility::removeFromArrayByKey($foreignRow, $ignoredFields);

                        $diff = array_diff($localRow, $foreignRow);
                        if (!empty($diff)) {
                            $differences[$table]['diff'][] = $uid;
                        }
                    } elseif ($localRowExists && !$foreignRowExists) {
                        $differences[$table]['only_local'][] = $uid;
                    } elseif (!$localRowExists && $foreignRowExists) {
                        $differences[$table]['only_foreign'][] = $uid;
                    }
                }
            }
        }
        foreach ($differences as $table => $places) {
            foreach ($places as $place => $values) {
                $differences[$table][$place] = implode(', ', $values);
            }
        }
        $this->view->assign('differences', $differences);
    }

    protected function getAllNonExcludedTables(): array
    {
        $tables = $this->localDatabase->getSchemaManager()->listTableNames();
        $excludedTables = $this->configContainer->get('excludeRelatedTables');
        return array_diff($tables, $excludedTables);
    }
}
