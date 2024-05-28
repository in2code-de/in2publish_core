# Known Issues

## File Preview URLs in Publish Files Module are not shown when using EXT:fal_securedownload

File Preview URLs are not rendered in the Publish Files Module, if EXT:fal_securedownload is installed.
However, the functionality of EXT:fal_securedownload and file publishing is not affected.

## File Preview URLs in Publish Files Module broken for non-public file storages

File Preview URLs rendered for files on Foreign are broken, if the file storage is not marked as public.

Reason: https://forge.typo3.org/issues/90330

## Context Menu Publishing

The context menu entry must be created very quickly to not degrade the overall context menu performance. This means,
that the context menu entry does not know about all conditions which have to be met for the record to be publishable.

The context menu is created if the current user is allowed to publish and 3rd party integrations allow the publishing of
this page. The context menu entry does not take into account if the record is actually changed. Publishing a page which
has no changes will result in the message "This record is not yet publishable".

## Orphaned MM records are ignored if foreign_table_where includes condition to joined table

Preconditions:

* You have a TCA select/inline relation with an MM table
* The column config defines an extra `foreign_table_where`
* The `foreign_table_where` contains a constraint on the foreign_table

When there are orphaned MM records, which means that the `uid_foreign` of the MM table points to a record in the joined
table which does not exist, the JOIN query will not return any result, hence the MM record is not fetched from the
database. This will not make a difference in most cases, as TYPO3 uses the same JOIN query to select records, but can
lead to differences if custom queries or code is used to handle MM records. Anyway, these orphaned MM records are
invalid and should be deleted. The community extension https://github.com/lolli42/dbdoctor has shown to be effective.
Manual operation might still be required though.
