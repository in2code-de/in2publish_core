import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { execInContainer, execTypo3Command } from '../../shared/helpers';

const project = process.env.COMPOSE_PROJECT_NAME ?? 'in2publish_core_v14';

function sql(query: string): string {
    const quoted = `'${query.replace(/'/g, `'"'"'`)}'`;
    return execInContainer(project, 'mysql',
        `MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -B -e ${quoted}`);
}

test('foreign file UID sequence warning is only shown in the Publisher Tools tests', async ({ page, backend }) => {
    const testsTab = backend.contentFrame.getByRole('link', { name: 'Tests', exact: true });
    const fileUidWarning = backend.contentFrame.locator('.callout-warning').filter({
        hasText: 'sys_file AUTO_INCREMENT',
    });
    const openTests = async () => {
        await backend.gotoModule('Publisher Tools');
        await testsTab.click();
        await expect(backend.contentFrame.locator('.callout-success').first()).toBeVisible({ timeout: 30000 });
    };
    const open = async (module: 'm1' | 'm3') => {
        await page.goto(new URL(`/typo3/module/in2publish_core/${module}`, config.local.baseUrl).href);
        await expect(backend.contentFrame.locator('.module-body, .module').first()).toBeVisible({ timeout: 30000 });
    };

    await backend.login();
    await openTests();
    await expect(backend.contentFrame.locator('.callout-danger')).toHaveCount(0);
    await expect(fileUidWarning).toHaveCount(0);
    await expect(backend.contentFrame.getByText('The foreign sys_file AUTO_INCREMENT does not exceed local.')).toBeVisible();

    const localSequence = Number(sql(
        "SET SESSION information_schema_stats_expiry = 0; SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'local' AND TABLE_NAME = 'sys_file'",
    ));
    const foreignSequence = Number(sql(
        "SET SESSION information_schema_stats_expiry = 0; SELECT AUTO_INCREMENT FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'foreign' AND TABLE_NAME = 'sys_file'",
    ));

    try {
        // An advanced sequence must warn even without adding any file rows.
        sql(`ALTER TABLE foreign.sys_file AUTO_INCREMENT = ${localSequence + 1000}`);
        execTypo3Command(project, 'local-php', 'cache:flush');

        await openTests();
        await expect(fileUidWarning).toBeVisible();
        await expect(fileUidWarning).toContainText(`(${localSequence + 1000}) exceeds local (${localSequence})`);
        await expect(fileUidWarning).toContainText('Upload files on local and publish them to foreign.');

        // The publishing modules no longer render test warnings.
        await open('m1');
        await expect(backend.contentFrame.locator('[data-publisher-test-warning]')).toHaveCount(0);
        await expect(backend.contentFrame.getByText('sys_file AUTO_INCREMENT')).toHaveCount(0);
        await open('m3');
        await expect(backend.contentFrame.locator('[data-publisher-test-warning]')).toHaveCount(0);
        await expect(backend.contentFrame.getByText('sys_file AUTO_INCREMENT')).toHaveCount(0);
    } finally {
        sql(`ALTER TABLE foreign.sys_file AUTO_INCREMENT = ${foreignSequence}`);
    }
});
