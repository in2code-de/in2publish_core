<?php
namespace In2code\In2publishCore\Domain\Service\Environment;

/**
 * Class AbstractService
 * @package In2code\In2publish\Domain\Service\Environment
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
