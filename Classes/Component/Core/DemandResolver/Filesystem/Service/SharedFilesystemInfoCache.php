<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Service;

use In2code\In2publishCore\Component\Core\DemandResolver\Filesystem\Model\FilesystemInformationCollection;
use TYPO3\CMS\Core\SingletonInterface;

use function json_encode;
use function ksort;
use function sha1;

class SharedFilesystemInfoCache implements SingletonInterface
{
    /** @var array<FilesystemInformationCollection> */
    protected array $local;
    /** @var array<FilesystemInformationCollection> */
    protected array $foreign;

    public function setLocal(FilesystemInformationCollection $response, array $request): void
    {
        ksort($request);
        $this->local[sha1(json_encode($request, JSON_THROW_ON_ERROR))] = $response;
    }

    public function setForeign(FilesystemInformationCollection $response, array $request): void
    {
        ksort($request);
        $this->foreign[sha1(json_encode($request, JSON_THROW_ON_ERROR))] = $response;
    }

    public function getLocal(array $request): ?FilesystemInformationCollection
    {
        ksort($request);
        return $this->local[sha1(json_encode($request, JSON_THROW_ON_ERROR))] ?? null;
    }

    public function getForeign(array $request): ?FilesystemInformationCollection
    {
        ksort($request);
        return $this->foreign[sha1(json_encode($request, JSON_THROW_ON_ERROR))] ?? null;
    }
}
