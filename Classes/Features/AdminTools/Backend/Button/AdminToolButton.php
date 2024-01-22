<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\AdminTools\Backend\Button;

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

use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;

use function htmlspecialchars;
use function trim;

class AdminToolButton extends LinkButton
{
    protected $showLabelText = true;

    protected $defaultClasses = [
        'btn', 'btn-default',
    ];

    public function isValid(): bool
    {
        return trim($this->getHref()) !== ''
            && trim($this->getTitle()) !== ''
            && $this->getType() === self::class;
    }

    public function render(): string
    {
        $attributes = [
            'href' => $this->getHref(),
            'class' => $this->getDefaultClassesAsString() . ' ' . $this->getClasses(),
            'title' => $this->getTitle(),
        ];
        $labelText = '';
        if ($this->showLabelText) {
            $labelText = ' ' . $this->title;
        }
        foreach ($this->dataAttributes as $attributeName => $attributeValue) {
            $attributes['data-' . $attributeName] = $attributeValue;
        }
        if ($this->isDisabled()) {
            $attributes['disabled'] = 'disabled';
            $attributes['class'] .= ' disabled';
        }
        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        $labelText = htmlspecialchars($labelText);

        return '<a ' . $attributesString . '>' . $labelText . '</a>';
    }

    protected function getDefaultClassesAsString(): string
    {
        return implode(' ', $this->defaultClasses);
    }

    public function makeButtonPrimary(): void
    {
        $index = array_search('btn-default', $this->defaultClasses, true);
        if (false !== $index) {
            $this->defaultClasses[$index] = 'btn-primary';
        }
    }
}
