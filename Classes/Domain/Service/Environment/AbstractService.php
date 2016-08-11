<?php
namespace In2code\In2publishCore\Domain\Service\Environment;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 in2code.de
 *  Alex Kellner <alexander.kellner@in2code.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class AbstractService
 */
abstract class AbstractService
{
    /**
     * Pattern to check if string is allowed or not
     *
     * @var string
     */
    protected $pattern = '~[^a-zA-Z0-9/._-]+~';

    /**
     * @var string
     */
    protected $substituteCharacter = '_';

    /**
     * Relative starting point like "fileadmin"
     *
     * @var string
     */
    protected $fileStoragePath = 'fileadmin';

    /**
     * Characters to replace
     *
     * @var array
     */
    protected $rewriteCharacters = array(
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'Ä' => 'Ae',
        'Ö' => 'Oe',
        'Ü' => 'Ue',
        'ß' => 'ss'
    );

    /**
     * Remove first folder from path (with beginning slash)
     *
     * @param string $folder
     * @return string
     */
    protected function removeFirstFolderFromPath($folder)
    {
        $folder = ltrim($folder, '/');
        $parts = explode('/', $folder);
        array_shift($parts);
        return '/' . implode('/', $parts);
    }

    /**
     * Remove last folder from path (with ending slash)
     *
     * @param string $folder
     * @return string
     */
    protected function removeLastFolderFromPath($folder)
    {
        if (!$this->isFolder($folder)) {
            $folder = $this->getPathFromPathAndFilename($folder);
        }
        $folder = rtrim($folder, '/');
        $parts = explode('/', $folder);
        array_pop($parts);
        return implode('/', $parts) . '/';
    }

    /**
     * @param string $pathAndFilename
     * @return string
     */
    protected function getPathFromPathAndFilename($pathAndFilename)
    {
        return dirname($pathAndFilename) . '/';
    }

    /**
     * @param string $pathAndFilename
     * @return string
     */
    protected function getFilenameFromPathAndFilename($pathAndFilename)
    {
        $pathParts = pathinfo($pathAndFilename);
        return $pathParts['basename'];
    }

    /**
     * @param string $fileOrFolder
     * @return bool
     */
    protected function isFolder($fileOrFolder)
    {
        return substr($fileOrFolder, -1) === '/';
    }
}
