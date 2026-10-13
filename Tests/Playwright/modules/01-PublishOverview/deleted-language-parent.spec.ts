import { test, expect } from '../../fixtures/setup-fixtures';
import config from '../../config';
import { execInContainer } from '../../shared/helpers';

const project = process.env.COMPOSE_PROJECT_NAME ?? 'in2publish_core_v14';
// UseCases 11i.1-11i.4 are stored in .project/data/dumps/{local,foreign}/pages.csv.
// The cases are children of page 1110 below EXT:in2publish_core (page 4).
// SQL is used only to read publication results; all starting states come from the dump.

function sql(database: 'local' | 'foreign', query: string): string {
    const quoted = `'${query.replace(/'/g, `'"'"'`)}'`;
    return execInContainer(project, 'mysql',
        `MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -B ${database} -e ${quoted}`);
}

async function openOverview(page: import('@playwright/test').Page): Promise<void> {
    await page.goto(new URL('/typo3/module/in2publish_core/m1?id=1110', config.local.baseUrl).href);
}

test.describe('Deleted language parent (UseCase 11i)', () => {
    test('publishing the default-language deletion also deletes its translation on foreign', async ({ page, backend }) => {
        test.setTimeout(180000);

        const defaultUid = 1100;
        const translationUid = 1101;

        await test.step('Delete the default language through the TYPO3 record module', async () => {
            await backend.login(config.local.baseUrl);
            await page.goto(new URL(`/typo3/record/edit?edit[pages][${defaultUid}]=edit`, config.local.baseUrl).href);
            await expect(backend.contentFrame.locator('a.t3js-editform-delete-record:not(.disabled)')).toBeVisible();
            await backend.contentFrame.locator('a.t3js-editform-delete-record:not(.disabled)').click();
            const modal = page.locator('typo3-backend-modal .modal, .modal.show').last();
            await expect(modal).toBeVisible();
            await modal.getByRole('button', { name: 'Delete', exact: true }).click();
            await expect.poll(() => sql('local',
                `SELECT CONCAT(uid, ':', deleted) FROM pages WHERE uid IN (${defaultUid}, ${translationUid}) ORDER BY uid`,
            )).toBe(`${defaultUid}:1\n${translationUid}:1`);
        });

        await test.step('Both language rows show their deletion without dependency warnings', async () => {
            await openOverview(page);
            const parent = backend.contentFrame.locator(`[data-record-identifier="pages-${defaultUid}"]`);
            const translation = backend.contentFrame.locator(`[data-record-identifier="pages-${translationUid}"]`);
            await expect(parent).toBeVisible();
            await expect(translation).toBeVisible();
            await expect(parent).toHaveAttribute('data-record-state', 'soft_deleted');
            await expect(translation).toHaveAttribute('data-record-state', 'soft_deleted');
            await expect(parent.locator('.icon-actions-exclamation-triangle-alt')).toHaveCount(0);
            await expect(translation.locator('.icon-actions-exclamation-triangle-alt')).toHaveCount(0);
            await expect(parent.locator('.icon-actions-arrow-right')).toBeVisible();
        });

        await test.step('Publishing the default language deletes both records on foreign', async () => {
            await backend.contentFrame.locator(
                `[data-record-identifier="pages-${defaultUid}"] .icon-actions-arrow-right`,
            ).click();
            await backend.waitUntilPublishingFinished();
            await expect(backend.contentFrame.locator('body')).toContainText(
                'The selected record has been published successfully',
            );
            await expect.poll(() => sql('foreign',
                `SELECT CONCAT(uid, ':', deleted) FROM pages WHERE uid IN (${defaultUid}, ${translationUid}) ORDER BY uid`,
            )).toBe(`${defaultUid}:1\n${translationUid}:1`);
        });
    });

    for (const { foreignParent, translationUid } of [
        { foreignParent: 'missing', translationUid: 1103 },
        { foreignParent: 'deleted', translationUid: 1105 },
    ]) {
        test(`the translation deletion has no warning when its parent is ${foreignParent} on foreign`,
            async ({ page, backend }) => {
                test.setTimeout(180000);

                await test.step('The translation can be published without a dependency warning', async () => {
                    await backend.login(config.local.baseUrl);
                    await openOverview(page);
                    const translation = backend.contentFrame.locator(
                        `[data-record-identifier="pages-${translationUid}"]`,
                    );
                    await expect(translation).toBeVisible();
                    await expect(translation.locator('.icon-actions-exclamation-triangle-alt')).toHaveCount(0);
                    await expect(translation.locator('.icon-actions-arrow-right')).toBeVisible();
                    await translation.locator('.icon-actions-arrow-right').click();
                    await backend.waitUntilPublishingFinished();
                    await expect.poll(() => sql('foreign',
                        `SELECT deleted FROM pages WHERE uid=${translationUid}`,
                    )).toBe('1');
                });
            });
    }

    test('an active translation still warns when its language parent is missing on foreign',
        async ({ page, backend }) => {
            test.setTimeout(180000);

            const translationUid = 1107;

            await test.step('Publish Overview shows a dependency warning on the translation', async () => {
                await backend.login(config.local.baseUrl);
                await openOverview(page);
                const translation = backend.contentFrame.locator(
                    `[data-record-identifier="pages-${translationUid}"]`,
                );
                await expect(translation).toBeVisible();
                await expect(translation.locator('.icon-actions-exclamation-triangle-alt')).toBeVisible();
                await expect(translation.locator('.icon-actions-arrow-right')).toHaveCount(0);
            });
        });
});
