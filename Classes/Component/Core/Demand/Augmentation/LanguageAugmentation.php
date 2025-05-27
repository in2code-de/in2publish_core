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

/**
 * This event listener listens to the event DemandsWereCollected and checks if there are languages in the request.
 * If there are, it adds a language condition to the demands.
 */
class LanguageAugmentation implements SingletonInterface
{
    public ?RecordTreeBuildRequest $request = null;

    public function __invoke(DemandsWereCollected $event): void
    {
        $languages = $this->request->getLanguages();
        if (null === $this->request || empty($languages)) {
            return;
        }
        $demands = $event->getDemands();
        $allDemands = $demands->getAll();

        $languages = array_map('intval', array_unique(array_merge([-1, 0], $languages)));

        $languageArray = implode(',', $languages);

        foreach ($allDemands as $type => $tables) {
            switch ($type) {
                case SysRedirectDemand::class:
                case SelectDemand::class:
                    foreach ($tables as $table => $wheres) {
                        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
                        if ($languageField) {
                            foreach ($wheres as $where => $value) {
                                if (trim($where) !== '') {
                                    $where .= ' AND ';
                                }
                                $where .= $table . '.' . $languageField . ' IN(' . $languageArray .')';
                                $allDemands[$type][$table][$where] = $value;
                            }
                        }
                    }
                    break;
                case JoinDemand::class:
                    foreach ($tables as $table => $joinTables) {
                        $languageField = $GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null;
                        if ($languageField) {
                            foreach ($joinTables as $joinTable => $wheres) {
                                foreach ($wheres as $where => $value) {
                                    if (trim($where) !== '') {
                                        $where .= ' AND ';
                                    }
                                    $where .= $table . '.' . $languageField . ' IN(' . $languageArray;
                                    $allDemands[$type][$table][$joinTable][$where] = $value;
                                }
                            }
                        }
                    }
                    break;
            }
        }
        $demands->setAll($allDemands);
    }
}
