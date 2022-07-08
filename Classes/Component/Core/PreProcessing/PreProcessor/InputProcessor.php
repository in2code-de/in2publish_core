<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\PreProcessing\PreProcessor;

use TYPO3\CMS\Core\Utility\GeneralUtility;

use function in_array;

class InputProcessor extends TextProcessor
{
    protected string $type = 'input';

    protected function additionalPreProcess(string $table, string $column, array $tca): array
    {
        if ($tca['renderType'] === 'inputLink') {
            return [];
        }

        if (isset($tca['softref'])) {
            $softRef = GeneralUtility::trimExplode(',', $tca['softref'] ?? '', true);
            if (in_array('typolink', $softRef, true) || in_array('typolink_tag', $softRef, true)) {
                return [];
            }
        }
        return [
            'An input field must either have renderType="inputLink" or softref="typolink" or softref="typolink_tag"',
        ];
    }
}
