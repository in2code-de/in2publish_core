<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\TcaHandling\PreProcessing\Service;

use function array_key_exists;
use function array_keys;
use function is_array;

class FlexFormFlatteningService
{
    /**
     * Simplify flexform definition
     *
     *      'sheets' => array(
     *          'sDEF' => array(
     *              'ROOT' => array(
     *                  'TCEforms' => array(
     *                      'sheetTitle' => 'Common'
     *                  ),
     *                  'type' => 'array',
     *                  'el' => array(
     *                      'settings.pid' => array(
     *                          'TCEforms' => array(
     *                              'exclude' => '1',
     *                              'label' => 'test',
     *                              'config' => array(
     *                                  'type' => 'group'
     *                              )
     *                          )
     *                      )
     *                  )
     *              )
     *          )
     *      )
     *
     *      =>
     *
     *      'settings.pid' => array(
     *          'exclude' => '1',
     *          'label' => 'test',
     *          'config' => array(
     *              'type' => 'group'
     *          )
     *      )
     *
     * @param array $flexFormDefinition
     *
     * @return array
     */
    public function flattenFlexFormDefinition(array $flexFormDefinition): array
    {
        $flattenedDefinition = [];
        foreach ($flexFormDefinition['sheets'] as $sheetDefinition) {
            foreach ($sheetDefinition as $rootDefinition) {
                if (is_array($rootDefinition) && !empty($rootDefinition['el'])) {
                    foreach ($rootDefinition['el'] as $fieldKey => $fieldDefinition) {
                        $flattenedDefinition = $this->flattenFieldFlexForm(
                            $flattenedDefinition,
                            $fieldDefinition,
                            $fieldKey
                        );
                    }
                }
            }
        }
        return $flattenedDefinition;
    }

    /**
     * Simplify flexform definition for a single field
     *
     *      'key' => array(
     *          'TCEforms' => array(
     *              'exclude' => '1',
     *              'label' => 'test',
     *              'config' => array(
     *                  'type' => 'group'
     *              )
     *          )
     *      )
     *
     *      =>
     *
     *      'key' => array(
     *          'exclude' => '1',
     *          'label' => 'test',
     *          'config' => array(
     *              'type' => 'group'
     *          )
     *      )
     *
     * @param array $flattenedDefinition
     * @param array $fieldDefinition
     * @param string $fieldKey
     *
     * @return array
     */
    protected function flattenFieldFlexForm(array $flattenedDefinition, array $fieldDefinition, string $fieldKey): array
    {
        // default FlexForm for a single field
        if (array_key_exists('TCEforms', $fieldDefinition)) {
            $flattenedDefinition[$fieldKey] = $fieldDefinition['TCEforms'];
        } elseif (array_key_exists('el', $fieldDefinition)) {
            // advanced FlexForm for a single field with n subfields
            $fieldDefinition = $fieldDefinition['el'];
            foreach (array_keys($fieldDefinition) as $subKey) {
                if (array_key_exists('el', $fieldDefinition[$subKey])) {
                    foreach ($fieldDefinition[$subKey]['el'] as $subFieldKey => $subFieldDefinition) {
                        $newFieldKey = $fieldKey . '.[ANY].' . $subKey . '.' . $subFieldKey;
                        $flattenedDefinition = $this->flattenFieldFlexForm(
                            $flattenedDefinition,
                            $subFieldDefinition,
                            $newFieldKey
                        );
                    }
                }
            }
        } elseif (array_key_exists('config', $fieldDefinition)) {
            $flattenedDefinition[$fieldKey] = $fieldDefinition;
        }
        return $flattenedDefinition;
    }
}
