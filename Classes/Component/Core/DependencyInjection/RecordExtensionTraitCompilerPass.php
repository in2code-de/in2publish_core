<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\Core\DependencyInjection;

use In2code\In2publishCore\Component\Core\DependencyInjection\RecordExtensionProvider\RecordExtensionsProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @codeCoverageIgnore
 */
class RecordExtensionTraitCompilerPass implements CompilerPassInterface
{
    private string $tagName;

    public function __construct(string $tagName)
    {
        $this->tagName = $tagName;
    }

    public function process(ContainerBuilder $container)
    {
        $recordExtensions = [];

        foreach (array_keys($container->findTaggedServiceIds($this->tagName)) as $serviceName) {
            /** @var RecordExtensionsProvider $recordExtensionsProvider */
            $recordExtensionsProvider = GeneralUtility::makeInstance($serviceName);
            foreach ($recordExtensionsProvider->getExtensions() as $recordExtension) {
                $recordExtensions[] = $recordExtension;
            }
        }

        $recordExtensions = array_unique($recordExtensions);

        $recordExtensionCode = '';
        foreach ($recordExtensions as $recordExtension) {
            $recordExtensionCode .= str_repeat(' ', 4) . 'use \\' . $recordExtension . ';' . PHP_EOL;
        }

        $recordExtensionTraitCode = file_get_contents(__DIR__ . '/../Record/Model/Extension/RecordExtensionTrait.php');
        $compiledRecordExtensionTraitCode = str_replace(
            '//###USES###',
            rtrim(ltrim($recordExtensionCode, ' '), PHP_EOL),
            $recordExtensionTraitCode,
        );

        $file = Environment::getVarPath() . '/cache/code/content_publisher/record_extension_trait.php';
        GeneralUtility::mkdir_deep(PathUtility::dirname($file));
        if (file_exists($file)) {
            unlink($file);
        }
        file_put_contents($file, $compiledRecordExtensionTraitCode);

        // Instantly load the file to prevent loading of the original
        require_once $file;
    }
}
