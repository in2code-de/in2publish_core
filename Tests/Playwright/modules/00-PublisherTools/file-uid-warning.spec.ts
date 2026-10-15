import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { execInContainer, execTypo3Command } from '../../shared/helpers';

const project = process.env.COMPOSE_PROJECT_NAME ?? 'in2publish_core_v14';

function sql(query: string): string {
    const quoted = `'${query.replace(/'/g, `'"'"'`)}'`;
    return execInContainer(project, 'mysql',
        `MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -B -e ${quoted}`);
}

test('foreign file UID sequence warning can be accepted across both publishing modules', async ({ page, backend }) => {
    await backend.login();
    await backend.gotoModule('Publisher Tools');
    await backend.contentFrame.getByRole('link', { name: 'Tests', exact: true }).click();
    await expect(backend.contentFrame.locator('.callout-danger')).toHaveCount(0);
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
        const open = async (module: 'm1' | 'm3') => {
            await page.goto(new URL(`/typo3/module/in2publish_core/${module}`, config.local.baseUrl).href);
        };
        const warning = backend.contentFrame.locator('[data-publisher-test-warning]');
        await open('m1');
        await expect(warning).toBeVisible();
        await expect(warning).toContainText('sys_file AUTO_INCREMENT');
        await expect(warning).toContainText(`(${localSequence + 1000}) exceeds local (${localSequence})`);
        await open('m3');
        await expect(warning).toBeVisible();
        await warning.getByRole('button', { name: 'Accept notice', exact: true }).click();
        await expect(warning).toHaveCount(0);
        await page.reload();
        await expect(warning).toHaveCount(0);
        await open('m1');
        await expect(warning).toHaveCount(0);
        sql(`ALTER TABLE foreign.sys_file AUTO_INCREMENT = ${localSequence + 2000}`);
        await open('m3');
        await expect(warning).toHaveCount(0);

        // Acceptance suppresses subsequent checks for this backend user.
        sql("DELETE FROM local.sys_registry WHERE entry_namespace = 'tx_in2publishcore' AND entry_key = 'test_result'");
        await page.reload();
        await expect(warning).toHaveCount(0);
        await open('m3');
        await expect(warning).toHaveCount(0);
    } finally {
        sql(`ALTER TABLE foreign.sys_file AUTO_INCREMENT = ${foreignSequence}`);
    }
});
