<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Features\SystemInformationExport\Controller;

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

use In2code\In2publishCore\Features\AdminTools\Controller\AbstractAdminToolsController;
use In2code\In2publishCore\Features\SystemInformationExport\Service\SystemInformationExportService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

use function file_get_contents;
use function flush;
use function gmdate;
use function header;
use function is_array;
use function json_decode;
use function json_encode;
use function json_last_error;
use function json_last_error_msg;
use function ob_clean;
use function ob_end_clean;
use function ob_get_level;
use function strlen;
use function time;

use const JSON_THROW_ON_ERROR;

class SystemInformationExportController extends AbstractAdminToolsController
{

    protected SystemInformationExportService $sysInfoExportService;

    /**
     * @codeCoverageIgnore
     * @noinspection PhpUnused
     */
    public function injectSystemInformationExportService(SystemInformationExportService $sysInfoExportService): void
    {
        $this->sysInfoExportService = $sysInfoExportService;
    }

    public function sysInfoIndexAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function sysInfoShowAction(): ResponseInterface
    {
        $info = $this->sysInfoExportService->getSystemInformation();
        $this->view->assign('info', $info);
        $this->view->assign('infoJson', json_encode($info, JSON_THROW_ON_ERROR));
        return $this->htmlResponse();
    }

    public function sysInfoDecodeAction(string $json = ''): ResponseInterface
    {
        if (!empty($json)) {
            $info = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($info)) {
                $this->view->assign('info', $info);
            } else {
                $args = [json_last_error(), json_last_error_msg()];
                $this->addFlashMessage(
                    LocalizationUtility::translate(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod4.xlf:system_info.decode.json_error.details',
                        null,
                        $args
                    ),
                    LocalizationUtility::translate(
                        'LLL:EXT:in2publish_core/Resources/Private/Language/locallang_mod4.xlf:system_info.decode.json_error',
                    ),
                    AbstractMessage::ERROR,
                );
            }
        }
        $this->view->assign('infoJson', $json);
        return $this->htmlResponse();
    }

    /**
     * @SuppressWarnings(PHPMD.ExitExpression) Don't use PSR-14 for historic reasons. Revisit in in2publish_core v12.
     */
    public function sysInfoDownloadAction(): void
    {
        $info = $this->sysInfoExportService->getSystemInformation();
        $json = json_encode($info, JSON_THROW_ON_ERROR);

        $downloadName = 'cp_sysinfo_' . time() . '.json';
        header('Content-Disposition: attachment; filename="' . $downloadName . '"');
        header('Content-Type: text/json');
        header('Content-Length: ' . strlen($json));
        header("Cache-Control: ''");
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT', true, 200);
        ob_clean();
        flush();
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo $json;
        die;
    }

    public function sysInfoUploadAction(): ResponseInterface
    {
        try {
            /** @var array $file */
            $file = $this->request->getArgument('jsonFile');
        } catch (NoSuchArgumentException $e) {
            return $this->htmlResponse();
        }
        $content = file_get_contents($file['tmp_name']);
        return (new ForwardResponse('sysInfoDecode'))->withArguments(['json' => $content]);
    }
}
