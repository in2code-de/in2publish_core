<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\FileHandling\Exception;

use Exception;
use Throwable;

use function sprintf;

/**
 * @codeCoverageIgnore
 */
class FolderDoesNotExistOnBothSidesException extends Exception
{
    private const MESSAGE = 'Folder "%s" does not exist on both sides. Try %s instead.';
    public const CODE = 1656408621;
    private string $givenCombinedIdentifier;
    private string $rootLevelCombinedIdentifier;

    public function __construct(
        string $givenCombinedIdentifier,
        string $rootLevelCombinedIdentifier,
        Throwable $previous = null
    ) {
        parent::__construct(
            sprintf(self::MESSAGE, $givenCombinedIdentifier, $rootLevelCombinedIdentifier),
            self::CODE,
            $previous,
        );
        $this->givenCombinedIdentifier = $givenCombinedIdentifier;
        $this->rootLevelCombinedIdentifier = $rootLevelCombinedIdentifier;
    }

    public function getGivenCombinedIdentifier(): string
    {
        return $this->givenCombinedIdentifier;
    }

    public function getRootLevelCombinedIdentifier(): string
    {
        return $this->rootLevelCombinedIdentifier;
    }
}
