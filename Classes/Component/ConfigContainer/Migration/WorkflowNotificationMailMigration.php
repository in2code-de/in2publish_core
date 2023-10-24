<?php

declare(strict_types=1);

namespace In2code\In2publishCore\Component\ConfigContainer\Migration;

/*
 * For copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class WorkflowNotificationMailMigration extends AbstractMigration
{
    protected const MIGRATION_MESSAGE = 'The configuration path "workflow.states.mailNotify" has been changed to "features.workflowNotificationMail". Please update your settings. Your settings have been migrated on the fly.';

    public function migrate(array $config): array
    {
        if (isset($config['workflow']['states']['mailNotify'])) {
            // Migrate the configuration
            $config['features']['workflowNotificationMail'] = $config['workflow']['states']['mailNotify'];
            unset($config['workflow']['states']['mailNotify']);

            // Inform the user about the migration
            $this->addMessage(self::MIGRATION_MESSAGE);
        }
        return $config;
    }
}
