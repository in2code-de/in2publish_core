<?php

declare(strict_types=1);

namespace In2code\In2publishCore\ViewHelpers;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Stores a value which persists beyond template variable scopes.
 * Using this ViewHelper indicates a strong violation of the pattern that forbids information to bubble up in templates.
 * That said, you should not use it but refactor your code instead.
 *
 * Example set a value:
 *  <in2:register name="isOkay" value="1" />
 *
 * Example get a value:
 *  <f:if condition="{in2:register(name:'isOkay')}>...<f:if>
 */
class RegisterViewHelper extends AbstractViewHelper implements SingletonInterface
{
    protected const VALUE_NONE = "\0";

    /** @var array */
    protected $register = [];

    public function initializeArguments(): void
    {
        $this->registerArgument('name', 'string', 'Name of the register', true);
        $this->registerArgument('value', 'mixed', 'Value to set or omit to retrieve', false, self::VALUE_NONE);
    }

    /**
     * @return mixed
     */
    public function render()
    {
        /** @var string $name */
        $name = $this->arguments['name'];
        $value = $this->arguments['value'];

        if ($value !== self::VALUE_NONE) {
            $this->register[$name] = $value;
        }
        return $this->register[$name] ?? null;
    }
}
