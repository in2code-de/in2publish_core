<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\Record\Model;

use JsonException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function implode;
use function json_decode;

use const JSON_THROW_ON_ERROR;

abstract class AbstractDatabaseRecord extends AbstractRecord
{
    public const CTRL_PROP_LANGUAGE_FIELD = 'languageField';
    public const CTRL_PROP_TRANS_ORIG_POINTER_FIELD = 'transOrigPointerField';
    public const CTRL_PROP_DELETE = 'delete';
    public const CTRL_PROP_ENABLECOLUMNS = 'enablecolumns';
    // Defaults for values if the given CTRL key does not exist in the TCA ctrl section
    protected const CTRL_DEFAULT = [
        self::CTRL_PROP_LANGUAGE_FIELD => 0,
        self::CTRL_PROP_TRANS_ORIG_POINTER_FIELD => 0,
        self::CTRL_PROP_DELETE => false,
    ];
    protected string $table;

    public function getClassification(): string
    {
        return $this->table;
    }

    public function getForeignIdentificationProps(): array
    {
        return [
            'uid' => $this->getId(),
        ];
    }

    public function getLanguage(): int
    {
        return $this->getCtrlProp(self::CTRL_PROP_LANGUAGE_FIELD);
    }

    public function getTransOrigPointer(): int
    {
        return $this->getCtrlProp(self::CTRL_PROP_TRANS_ORIG_POINTER_FIELD);
    }

    /**
     * @return scalar
     */
    protected function getCtrlProp(string $ctrlName)
    {
        $value = self::CTRL_DEFAULT[$ctrlName] ?? null;

        $valueField = $GLOBALS['TCA'][$this->table]['ctrl'][$ctrlName] ?? null;

        if (null !== $valueField) {
            $value = $this->getProp($valueField) ?? $value;
        }

        return $value;
    }

    /**
     * @return array<Dependency>
     */
    public function calculateDependencies(): array
    {
        $dependencies = [];
        $this->calculateLanguageDependencies($dependencies);
        $this->calculateParentRecordDependencies($dependencies);
        return $dependencies;
    }

    protected function calculateLanguageDependencies(array &$dependencies): void
    {
        $language = $this->getCtrlProp(self::CTRL_PROP_LANGUAGE_FIELD);
        $transOrigPointer = $this->getCtrlProp(self::CTRL_PROP_TRANS_ORIG_POINTER_FIELD);
        if ($language > 0 && $transOrigPointer > 0) {
            $dependencies[] = $transOrigExisting = new Dependency(
                $this,
                $this->getClassification(),
                ['uid' => $transOrigPointer],
                Dependency::REQ_EXISTING,
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.requires_translation_parent.existing',
                fn (Record $record): array => [
                    $this->__toString() ?: "({$this->getClassification()} [{$this->getId()}])",
                    $record->__toString() ?: "({$record->getClassification()} [{$record->getId()}])",
                ],
            );

            $enableFieldLabels = $this->getInheritedEnableColumnsWithLabels();
            if (!empty($enableFieldLabels)) {
                $dependencies[] = $transOrigEnableColumns = new Dependency(
                    $this,
                    $this->getClassification(),
                    ['uid' => $transOrigPointer],
                    Dependency::REQ_ENABLECOLUMNS,
                    'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.requires_translation_parent.enablecolumns',
                    fn (Record $record): array => [
                        $this->__toString() ?: "({$this->getClassification()} [{$this->getId()}])",
                        implode(', ', $enableFieldLabels),
                        $record->__toString() ?: "({$record->getClassification()} [{$record->getId()}])",
                    ],
                );
                $transOrigEnableColumns->addSupersedingDependency($transOrigExisting);
            }
        }
    }

    protected function calculateParentRecordDependencies(array &$dependencies): void
    {
        $pid = $this->getProp('pid');
        if ($pid > 0) {
            $dependencies[] = $pageExisting = new Dependency(
                $this,
                'pages',
                ['uid' => $pid],
                Dependency::REQ_EXISTING,
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.requires_published_page.existing',
                fn (Record $record): array => [
                    $this->__toString() ?: "({$this->getClassification()} [{$this->getId()}])",
                    $record->__toString() ?: "({$record->getClassification()} [{$record->getId()}])",
                ],
            );

            $enableFieldLabels = [];
            foreach ($GLOBALS['TCA']['pages']['ctrl'][self::CTRL_PROP_ENABLECOLUMNS] ?? [] as $enableField) {
                $enableFieldLabels[] = $GLOBALS['LANG']->sL($GLOBALS['TCA']['pages']['columns'][$enableField]['label']);
            }

            $dependencies[] = $pageEnableColumns = new Dependency(
                $this,
                'pages',
                ['uid' => $pid],
                Dependency::REQ_ENABLECOLUMNS,
                'LLL:EXT:in2publish_core/Resources/Private/Language/locallang.xlf:record.reason.requires_published_page.enablecolumns',
                fn (Record $record): array => [
                    $this->__toString() ?: "({$this->getClassification()} [{$this->getId()}])",
                    $record->__toString() ?: "({$record->getClassification()} [{$record->getId()}])",
                    implode(', ', $enableFieldLabels),
                ],
            );
            $pageEnableColumns->addSupersedingDependency($pageExisting);
        }
    }

    public function __toString(): string
    {
        $labelField = $GLOBALS['TCA'][$this->table]['ctrl']['label'] ?? null;
        $labelAltField = $GLOBALS['TCA'][$this->table]['ctrl']['label_alt'] ?? null;
        $labelAltFields = [];
        if (null !== $labelAltField) {
            $labelAltFields = GeneralUtility::trimExplode(',', $labelAltField, true);
        }
        $labelAltForce = $GLOBALS['TCA'][$this->table]['ctrl']['label_alt_force'] ?? false;

        $labels = [];
        $label = $this->getClassification();
        if (null !== $labelField) {
            $label = $this->getProp($labelField);
        }
        if (null !== $labelField && !empty($label)) {
            $labels[] = $label;
        }
        if (empty($labels) || true === $labelAltForce) {
            foreach ($labelAltFields as $labelAltField) {
                $altLabel = $this->getProp($labelAltField);
                if (!empty($altLabel)) {
                    $labels[] = $altLabel;
                }
            }
        }
        return implode(', ', $labels);
    }

    /**
     * @return array<string, string> Key=column Value=Label
     */
    public function getInheritedEnableColumnsWithLabels(): array
    {
        $l10nState = [];
        $l10nStateEncoded = $this->getLocalProps()['l10n_state'] ?? null;
        if (!empty($l10nStateEncoded)) {
            try {
                $l10nState = json_decode($l10nStateEncoded, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
            }
        }
        $enableFieldLabels = [];
        $tca = $GLOBALS['TCA'][$this->table];
        $enableColumns = $tca['ctrl'][self::CTRL_PROP_ENABLECOLUMNS] ?? [];
        foreach ($enableColumns as $enableField) {
            // Ignore enablecolumns which are set on the child record.
            if (
                ($tca['columns'][$enableField]['config']['behaviour']['allowLanguageSynchronization'] ?? false)
                && 'custom' === ($l10nState[$enableField] ?? 'parent')
            ) {
                continue;
            }
            $label = $enableField;
            if (isset($tca['columns'][$enableField]['label'])) {
                $label = $GLOBALS['LANG']->sL($tca['columns'][$enableField]['label']);
            }
            $enableFieldLabels[$enableField] = $label;
        }
        return $enableFieldLabels;
    }

    public function isRemovedFromLocalDatabase(): bool
    {
        $deleteField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['delete'] ?? null;
        return (null !== $deleteField && array_key_exists($deleteField, $this->getForeignProps()) && (bool)$this->getForeignProps()[$deleteField]) && empty($this->getLocalProps());
    }

    public function isRemovedFromForeignDatabase(): bool
    {
        $deleteField = $GLOBALS['TCA'][$this->getClassification()]['ctrl']['delete'] ?? null;
        return (null !== $deleteField && array_key_exists($deleteField, $this->getLocalProps()) && (bool)$this->getLocalProps()[$deleteField]) && empty($this->getForeignProps());
    }
}
