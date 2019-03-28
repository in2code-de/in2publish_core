# Known Issues

## Publish Files

**Problem**: If an editor deletes a file and uploads a file with same name in the same path,
the publisher shows two entries in the Publish Files module

**Solution**: Rename the file in the stage system, in order to be able to perform the deletion
process. Then publish the renamed file (or rename it again).

## TYPO3 v9 Preview & Compare URLs

The sites configuration introduced in TYPO3 v9 is not yet fully supported when building URLs for the overview module.
Building frontend links in the backend has always been an annoying task, but with the introduction of the sites config it just went worse.

## TYPO3 v9 Publish Overview translated pages

When selecting a page in the page tree the publish overview will show everything as expected, except for translated pages.
In TYPO3 9.0 pages_language_overlay was removed and replaced by pages with a sys_language_uid. This is not yet supported. 
