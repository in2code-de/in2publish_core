<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Demand\Augmentation;

use In2code\In2publishCore\Component\Core\Demand\Type\JoinDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SelectDemand;
use In2code\In2publishCore\Component\Core\Demand\Type\SysRedirectDemand;
use In2code\In2publishCore\Component\Core\RecordTree\RecordTreeBuildRequest;
use In2code\In2publishCore\Event\DemandsWereCollected;
use TYPO3\CMS\Core\SingletonInterface;

use function array_map;
use function array_merge;
use function array_unique;
use function implode;
use function trim;

class LanguageAugmentation implements SingletonInterface
{
    public ?RecordTreeBuildRequest $request = null;

    public function __invoke(DemandsWereCollected $event): void
    {
        if (null === $this->request?->getLanguages()) {
            return;
        }

        $languages = array_map('intval', array_unique(array_merge([-1, 0], $this->request->getLanguages())));
        $languageArray = implode(',', $languages);

        $demands = $event->getDemands();
        $allDemands = $demands->getAll();

        foreach ($allDemands as $type => $tables) {
            $allDemands[$type] = match ($type) {
                SelectDemand::class,
                SysRedirectDemand::class => $this->augmentFlatDemands($tables, $languageArray),
                JoinDemand::class => $this->augmentJoinDemands($tables, $languageArray),
                default => $tables,
            };
        }

        $demands->setAll($allDemands);
    }

    private function augmentFlatDemands(array $tables, string $languageArray): array
    {
        foreach ($tables as $table => $wheres) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
            if (null === $languageField) {
                continue;
            }
            foreach ($wheres as $where => $value) {
                $newWhere = $this->buildLanguageWhere((string)$where, $table, $languageField, $languageArray);
                $tables[$table][$newWhere] = $value;
            }
        }
        return $tables;
    }

    private function augmentJoinDemands(array $tables, string $languageArray): array
    {
        foreach ($tables as $table => $joinTables) {
            $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
            if (null === $languageField) {
                continue;
            }
            foreach ($joinTables as $joinTable => $wheres) {
                foreach ($wheres as $where => $value) {
                    $newWhere = $this->buildLanguageWhere((string)$where, $table, $languageField, $languageArray);
                    $tables[$table][$joinTable][$newWhere] = $value;
                }
            }
        }
        return $tables;
    }

    private function buildLanguageWhere(string $where, string $table, string $languageField, string $languageArray): string
    {
        $prefix = trim($where) !== '' ? $where . ' AND ' : '';
        return $prefix . $table . '.' . $languageField . ' IN(' . $languageArray . ')';
    }
}