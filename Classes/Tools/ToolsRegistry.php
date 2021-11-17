<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Tools;

/*
 * Copyright notice
 *
 * (c) 2017 in2code.de and the following authors:
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

use In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry as NewToolsRegistry;
use TYPO3\CMS\Core\SingletonInterface;

use function user_error;

use const E_USER_DEPRECATED;

/**
 * @deprecated This class is superseded by \In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry and admin
 *  tools registration via Services.yaml. See Documentation/Guides/CustomTools.md
 */
class ToolsRegistry implements SingletonInterface
{
    private const DEPRECATED_ROOT_TOOLS_REGISTRY = 'Using the ToolsRegistry is deprecated. Please register your tool via service tag. See Documentation/Guides/CustomTools.md';
    private const DEPRECATED_GET_TOOLS = 'The class '
                                         . self::class
                                         . ' is deprecated. Please use '
                                         . NewToolsRegistry::class
                                         . ' instead.';
    private const DEPRECATED_REMOVE_TOOLS = 'The method removeTool of the old ToolsRegistry is deprecated and will be removed in in2publish_core v11. There is no replacement.';

    protected NewToolsRegistry $toolsRegistry;

    public function __construct(NewToolsRegistry $toolsRegistry)
    {
        $this->toolsRegistry = $toolsRegistry;
    }

    /**
     * @deprecated Using the ToolsRegistry is deprecated. Please register your tool via service tag.
     *  See Documentation/Guides/CustomTools.md
     */
    public function addTool(
        string $name,
        string $description,
        string $controller,
        string $action
    ): void {
        user_error(self::DEPRECATED_ROOT_TOOLS_REGISTRY, E_USER_DEPRECATED);
        $this->toolsRegistry->addTool(
            $controller,
            $name,
            $description,
            $action
        );
    }

    /**
     * @deprecated This class is deprecated. Use \In2code\In2publishCore\Features\AdminTools\Service\ToolsRegistry
     *  instead. See Documentation/Guides/CustomTools.md
     */
    public function getTools(): array
    {
        user_error(self::DEPRECATED_GET_TOOLS, E_USER_DEPRECATED);
        return $this->toolsRegistry->getEntries();
    }

    /**
     * @param string $name
     * @deprecated The method removeTool of the old ToolsRegistry is deprecated
     *  and will be removed in in2publish_core v11. There is no replacement.
     */
    public function removeTool(string $name): void
    {
        user_error(self::DEPRECATED_REMOVE_TOOLS, E_USER_DEPRECATED);
    }
}
